<?php

namespace Voodflow\Voodflow\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Voodflow\Voodflow\Filament\Resources\InstalledPackageResource\Pages;
use Voodflow\Voodflow\Models\InstalledPackage;
use ZipArchive;

class InstalledPackageResource extends Resource
{
    protected static ?string $model = InstalledPackage::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Node Plugins';

    protected static ?string $modelLabel = 'Plugin';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->disabled()
                    ->helperText('Internal plugin ID'),
                TextInput::make('display_name')
                    ->label('Name')
                    ->disabled(),
                TextInput::make('version')
                    ->disabled(),
                TextInput::make('license_key')
                    ->label('Anystack License Key')
                    ->password()
                    ->revealable()
                    ->helperText('Enter your license key to activate premium features (if required)'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Disable to hide this node from the editor'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable()
                    ->description(fn ($record) => $record->description),
                TextColumn::make('name')
                    ->label('ID')
                    ->color('gray')
                    ->size('sm')
                    ->toggleable(),
                TextColumn::make('version')
                    ->badge()
                    ->color('info'),
                TextColumn::make('type')
                    ->badge(),
                ToggleColumn::make('is_active'),
            ])
            ->headerActions([
                Action::make('install')
                    ->label('Upload Plugin')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form(function (Schema $schema) {
                        return $schema->components([
                            FileUpload::make('package')
                                ->label('Plugin Package (.zip)')
                                ->disk('local')
                                ->directory('voodflow-temp')
                                ->acceptedFileTypes(['application/zip'])
                                ->required(),
                        ]);
                    })
                    ->action(function (array $data) {
                        try {
                            $path = Storage::disk('local')->path($data['package']);
                            static::installFromZip($path);
                            // Cleanup temp file
                            Storage::disk('local')->delete($data['package']);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Installation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function installFromZip(string $zipPath)
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Could not open ZIP file');
        }

        // Search for manifest.json
        $manifestContent = null;
        $rootPrefix = '';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (str_ends_with($filename, 'manifest.json')) {
                // Should be root manifest or immediate subfolder
                $parts = explode('/', trim($filename, '/'));
                if (count($parts) <= 2) {
                    $manifestContent = $zip->getFromIndex($i);
                    $rootPrefix = dirname($filename) == '.' ? '' : dirname($filename) . '/';

                    break;
                }
            }
        }

        if (! $manifestContent) {
            $zip->close();

            throw new \Exception('manifest.json not found in the package');
        }

        $manifest = json_decode($manifestContent, true);
        if (! $manifest || ! isset($manifest['name'])) {
            $zip->close();

            throw new \Exception('Invalid manifest.json: name is required');
        }

        $pluginName = $manifest['name'];

        // Installation path: storage/app/voodflow/nodes/package-name
        $installPath = storage_path('app/voodflow/nodes/' . $pluginName);

        if (! File::isDirectory($installPath)) {
            File::makeDirectory($installPath, 0755, true);
        }

        // Extract files
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Skip directories
            if (str_ends_with($filename, '/')) {
                continue;
            }

            // Handle root prefix (strip it if extracting content directly)
            $relativePath = $filename;
            if ($rootPrefix && str_starts_with($filename, $rootPrefix)) {
                $relativePath = substr($filename, strlen($rootPrefix));
            }

            if (! $relativePath) {
                continue;
            }

            $destPath = $installPath . '/' . $relativePath;
            $destDir = dirname($destPath);

            if (! File::isDirectory($destDir)) {
                File::makeDirectory($destDir, 0755, true);
            }

            copy("zip://{$zipPath}#{$filename}", $destPath);
        }

        $zip->close();

        // Update database
        InstalledPackage::updateOrCreate(
            ['name' => $pluginName],
            [
                'display_name' => $manifest['display_name'] ?? $pluginName,
                'version' => $manifest['version'] ?? '1.0.0',
                'description' => $manifest['description'] ?? '',
                'path' => $installPath,
                'metadata' => $manifest,
                'is_active' => true,
                'type' => 'node',
            ]
        );

        Notification::make()
            ->title('Plugin Installed Successfully')
            ->body("Node '{$pluginName}' is now available.")
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstalledPackages::route('/'),
            'edit' => Pages\EditInstalledPackage::route('/{record}/edit'),
        ];
    }
}

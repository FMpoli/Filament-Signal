<?php

namespace Voodflow\Voodflow\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Voodflow\Voodflow\Filament\Resources\WorkflowResource\Pages;
use Voodflow\Voodflow\Models\Workflow;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-sparkles';

    public static function getNavigationGroup(): ?string
    {
        return __('voodflow::signal.plugin.navigation.group');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Group::make([
                    Section::make()
                        ->icon('heroicon-o-bolt')
                        ->compact()
                        ->heading(fn (Workflow $record) => $record->name)
                        ->schema([
                            TextEntry::make('description')
                                ->label(__('voodflow::signal.fields.description'))
                                ->placeholder('—')
                                ->visible(fn (Workflow $record) => ! empty($record->description))
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpan(12),
            ]);
    }

    public static function getNavigationLabel(): string
    {
        return 'Workflows'; // Temporary label
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Group::make([
                    Section::make('Workflow Details')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required(),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'active' => 'Active',
                                    'disabled' => 'Disabled',
                                ])
                                ->default('draft')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Node Icons Preview (first 3-5 nodes)
                Tables\Columns\TextColumn::make('nodes_preview')
                    ->label('Nodes')
                    ->state(function (Workflow $record) {
                        $nodes = $record->nodes_preview ?? [];
                        if (empty($nodes)) {
                            return '—';
                        }
                        return count($nodes) . ' nodes';
                    })
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-squares-2x2')
                    ->wrap(false),
                
                // Workflow Name
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Workflow $record) => $record->description)
                    ->limit(30),
                
                // Author
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->default('—'),
                
                // Trigger Type (with icon and tooltip)
                Tables\Columns\TextColumn::make('trigger_info')
                    ->label('Trigger')
                    ->state(function (Workflow $record) {
                        $triggerNode = $record->getTriggerNode();
                        
                        if (!$triggerNode) {
                            return 'Not set';
                        }
                        
                        $data = $triggerNode->data ?? [];
                        $event = $data['selectedEvent'] ?? null;
                        
                        if (!$event) {
                            return 'Not set';
                        }
                        
                        $parts = explode('\\', $event);
                        $className = end($parts);
                        
                        if (str_contains(strtolower($event), 'schedule') || str_contains(strtolower($event), 'cron')) {
                            return 'Schedule';
                        } elseif (str_contains(strtolower($event), 'webhook') || str_contains(strtolower($event), 'http')) {
                            return 'Webhook';
                        } elseif (str_contains(strtolower($event), 'eloquent') || 
                                 str_contains(strtolower($className), 'created') ||
                                 str_contains(strtolower($className), 'updated') ||
                                 str_contains(strtolower($className), 'deleted')) {
                            return 'Database';
                        }
                        
                        return 'Event';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'Schedule' => 'info',
                        'Webhook' => 'success',
                        'Database' => 'warning',
                        'Event' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'Schedule' => 'heroicon-o-clock',
                        'Webhook' => 'heroicon-o-globe-alt',
                        'Database' => 'heroicon-o-database',
                        'Event' => 'heroicon-o-bolt',
                        default => 'heroicon-o-minus-circle',
                    })
                    ->wrap(false)
                    ->alignCenter(),
                
                // Execution Count
                Tables\Columns\TextColumn::make('executions_count')
                    ->label('Executions')
                    ->counts('executions')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-play'),
                
                // Status
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'active',
                        'danger' => 'disabled',
                    ])
                    ->sortable(),
                
                // Created At
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->description(fn (Workflow $record) => $record->created_at?->diffForHumans()),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'disabled' => 'Disabled',
                    ]),
            ])
            ->actions([
                Action::make('flow')
                    ->label('Editor')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('primary')
                    ->url(fn (Workflow $record) => static::getUrl('flow', ['record' => $record])),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflows::route('/'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'view' => Pages\ViewWorkflow::route('/{record}'),
            'edit' => Pages\EditWorkflow::route('/{record}/edit'),
            'flow' => Pages\FlowWorkflow::route('/{record}/flow'),
        ];
    }
}

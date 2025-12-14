<?php

namespace Voodflow\Voodflow\Filament\Resources;

use BackedEnum;
use Voodflow\Voodflow\Filament\Resources\TemplateResource\Pages;
use Voodflow\Voodflow\Models\Template;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-envelope';

    public static function getNavigationGroup(): ?string
    {
        return __('voodflow::signal.plugin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodflow::signal.plugin.navigation.templates');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('voodflow::signal.fields.name'))
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')
                        ->label(__('voodflow::signal.fields.slug'))
                        ->required()
                        ->unique(SignalTemplate::class, 'slug', ignoreRecord: true),
                    Forms\Components\TextInput::make('subject')
                        ->label(__('voodflow::signal.fields.subject'))
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('voodflow::signal.fields.status'))
                        ->default(true),
                ])
                ->columns(1),
            Section::make(__('voodflow::signal.sections.template_content'))
                ->schema([
                    Forms\Components\RichEditor::make('content_html')
                        ->label(__('voodflow::signal.fields.content_html'))
                        ->required()

                        ->toolbarButtons(config('voodflow.editor.tiptap.toolbar_buttons')),
                    Forms\Components\Textarea::make('content_text')
                        ->label(__('voodflow::signal.fields.content_text'))
                        ->rows(4),
                ])
                ->columns(1),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('voodflow::signal.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('voodflow::signal.fields.slug'))
                    ->copyable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('voodflow::signal.fields.status'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label(__('voodflow::signal.fields.updated_at')),
            ])
            ->actions([
                ViewAction::make()->slideOver(),
                EditAction::make()->slideOver(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemplates::route('/'),
        ];
    }
}

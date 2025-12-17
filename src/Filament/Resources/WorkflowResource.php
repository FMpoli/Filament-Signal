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
                    ->formatStateUsing(function ($state, Workflow $record) {
                        $nodes = $record->nodes_preview ?? [];
                        $maxDisplay = 4;
                        $displayNodes = array_slice($nodes, 0, $maxDisplay);
                        $remaining = count($nodes) - $maxDisplay;
                        
                        if (empty($nodes)) {
                            return '<span class="text-xs text-gray-400">—</span>';
                        }
                        
                        $html = '<div class="flex items-center gap-1.5">';
                        
                        foreach ($displayNodes as $nodeInfo) {
                            $type = $nodeInfo['type'] ?? 'unknown';
                            $icon = $nodeInfo['icon'] ?? 'heroicon-o-puzzle-piece';
                            
                            $bgColor = match($type) {
                                'trigger' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
                                'action' => 'bg-green-500/10 text-green-600 dark:text-green-400',
                                'filter' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
                                'conditional' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                                default => 'bg-gray-500/10 text-gray-600 dark:text-gray-400',
                            };
                            
                            $html .= '<div class="flex items-center justify-center w-7 h-7 rounded-full ' . $bgColor . '" title="' . ucfirst($type) . '">';
                            $html .= '<x-filament::icon icon="' . $icon . '" class="w-3.5 h-3.5" />';
                            $html .= '</div>';
                        }
                        
                        if ($remaining > 0) {
                            $html .= '<div class="flex items-center justify-center w-7 h-7 rounded-full bg-gray-500/10 text-gray-600 dark:text-gray-400 text-[10px] font-semibold">';
                            $html .= '+' . $remaining;
                            $html .= '</div>';
                        }
                        
                        $html .= '</div>';
                        
                        return $html;
                    })
                    ->html()
                    ->wrap(false)
                    ->width('150px'),
                
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
                    ->formatStateUsing(function ($state, Workflow $record) {
                        $triggerNode = $record->getTriggerNode();
                        $icon = 'heroicon-o-minus-circle';
                        $label = '—';
                        $tooltip = 'No trigger';
                        $colorClass = 'text-gray-400';
                        
                        if ($triggerNode) {
                            $data = $triggerNode->data ?? [];
                            $event = $data['selectedEvent'] ?? null;
                            
                            if ($event) {
                                $parts = explode('\\', $event);
                                $className = end($parts);
                                
                                $isScheduled = str_contains(strtolower($event), 'schedule') || 
                                              str_contains(strtolower($event), 'cron');
                                $isWebhook = str_contains(strtolower($event), 'webhook') ||
                                            str_contains(strtolower($event), 'http');
                                $isEloquent = str_contains(strtolower($event), 'eloquent') ||
                                             str_contains(strtolower($className), 'created') ||
                                             str_contains(strtolower($className), 'updated') ||
                                             str_contains(strtolower($className), 'deleted');
                                
                                if ($isScheduled) {
                                    $icon = 'heroicon-o-clock';
                                    $label = 'Schedule';
                                    $tooltip = 'Scheduled trigger';
                                    $colorClass = 'text-blue-600 dark:text-blue-400';
                                } elseif ($isWebhook) {
                                    $icon = 'heroicon-o-globe-alt';
                                    $label = 'Webhook';
                                    $tooltip = 'HTTP webhook';
                                    $colorClass = 'text-green-600 dark:text-green-400';
                                } elseif ($isEloquent) {
                                    $icon = 'heroicon-o-database';
                                    $label = 'Database';
                                    $tooltip = 'Eloquent: ' . htmlspecialchars($className);
                                    $colorClass = 'text-purple-600 dark:text-purple-400';
                                } else {
                                    $icon = 'heroicon-o-bolt';
                                    $label = 'Event';
                                    $tooltip = htmlspecialchars($className);
                                    $colorClass = 'text-amber-600 dark:text-amber-400';
                                }
                            }
                        }
                        
                        return '<div class="flex items-center justify-center gap-1.5" title="' . htmlspecialchars($tooltip) . '">' .
                               '<x-filament::icon icon="' . $icon . '" class="w-4 h-4 ' . $colorClass . '" />' .
                               '<span class="text-xs font-medium ' . $colorClass . '">' . htmlspecialchars($label) . '</span>' .
                               '</div>';
                    })
                    ->html()
                    ->wrap(false)
                    ->width('120px')
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

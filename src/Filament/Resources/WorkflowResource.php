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
                            return 'None';
                        }
                        
                        $config = $triggerNode->config ?? [];
                        
                        // Try different possible field names
                        $event = $config['selectedEvent'] 
                              ?? $config['event'] 
                              ?? $config['eventClass']
                              ?? $config['trigger_event']
                              ?? null;
                        
                        if (!$event) {
                            // If there's any config, it's configured but we don't recognize the structure
                            if (!empty($config)) {
                                return 'Event'; // Generic fallback
                            }
                            return 'Unconfigured';
                        }
                        
                        $eventLower = strtolower($event);
                        $parts = explode('\\', $event);
                        $className = end($parts);
                        
                        // Detect trigger type based on event class name
                        if (str_contains($eventLower, 'schedule') || str_contains($eventLower, 'cron')) {
                            return 'Schedule';
                        } elseif (str_contains($eventLower, 'webhook') || str_contains($eventLower, 'http')) {
                            return 'Webhook';
                        } elseif (str_contains($eventLower, 'subflow') || str_contains($eventLower, 'chain')) {
                            return 'Subflow';
                        } elseif (str_contains($eventLower, 'manual') || str_contains($eventLower, 'button')) {
                            return 'Manual';
                        } elseif (str_contains($eventLower, 'eloquent') || 
                                 str_contains(strtolower($className), 'created') ||
                                 str_contains(strtolower($className), 'updated') ||
                                 str_contains(strtolower($className), 'deleted') ||
                                 str_contains(strtolower($className), 'saved')) {
                            return 'Event';
                        }
                        
                        // Default to Event for any other internal event
                        return 'Event';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'Schedule' => 'info',
                        'Webhook' => 'success',
                        'Event' => 'warning',
                        'Subflow' => 'purple',
                        'Manual' => 'gray',
                        'Unconfigured' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'Schedule' => 'heroicon-o-clock',
                        'Webhook' => 'heroicon-o-globe-alt',
                        'Event' => 'heroicon-o-bolt',
                        'Subflow' => 'heroicon-o-arrow-path',
                        'Manual' => 'heroicon-o-hand-raised',
                        'Unconfigured' => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-minus-circle',
                    })
                    ->tooltip(function (Workflow $record) {
                        $triggerNode = $record->getTriggerNode();
                        if (!$triggerNode) return 'No trigger configured';
                        
                        $config = $triggerNode->config ?? [];
                        $event = $config['selectedEvent'] ?? $config['event'] ?? null;
                        
                        if ($event) {
                            $parts = explode('\\', $event);
                            return end($parts);
                        }
                        
                        return 'Trigger configured';
                    })
                    ->wrap(false)
                    ->alignCenter(),
                
                // Execution Count
                Tables\Columns\TextColumn::make('executions_count')
                    ->label('Runs')
                    ->counts('executions')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-play'),
                
                // Last Run Status
                Tables\Columns\TextColumn::make('last_execution_status')
                    ->label('Last Run')
                    ->state(function (Workflow $record) {
                        $lastExecution = $record->executions()->latest()->first();
                        
                        if (!$lastExecution) {
                            return 'Never';
                        }
                        
                        return $lastExecution->status ?? 'Unknown';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'completed' => 'success',
                        'success' => 'success',
                        'failed' => 'danger',
                        'error' => 'danger',
                        'running' => 'info',
                        'pending' => 'warning',
                        'Never' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match($state) {
                        'completed', 'success' => 'heroicon-o-check-circle',
                        'failed', 'error' => 'heroicon-o-x-circle',
                        'running' => 'heroicon-o-arrow-path',
                        'pending' => 'heroicon-o-clock',
                        default => 'heroicon-o-minus',
                    })
                    ->description(function (Workflow $record) {
                        $lastExecution = $record->executions()->latest()->first();
                        return $lastExecution?->created_at?->diffForHumans() ?? '—';
                    })
                    ->alignCenter(),
                
                // Average Duration
                Tables\Columns\TextColumn::make('avg_duration')
                    ->label('Avg Time')
                    ->state(function (Workflow $record) {
                        $avgDuration = $record->executions()
                            ->whereNotNull('finished_at')
                            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, finished_at)) as avg_seconds')
                            ->value('avg_seconds');
                        
                        if (!$avgDuration) {
                            return '—';
                        }
                        
                        if ($avgDuration < 60) {
                            return round($avgDuration, 1) . 's';
                        } elseif ($avgDuration < 3600) {
                            return round($avgDuration / 60, 1) . 'm';
                        } else {
                            return round($avgDuration / 3600, 1) . 'h';
                        }
                    })
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                
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

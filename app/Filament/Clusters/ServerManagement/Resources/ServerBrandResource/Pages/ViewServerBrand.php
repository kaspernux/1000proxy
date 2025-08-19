<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewServerBrand extends ViewRecord
{
    protected static string $resource = ServerBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('primary'),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Infolists\Components\Section::make('Brand Overview')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Brand Name')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description')
                                    ->markdown()
                                    ->columnSpanFull(),

                                Infolists\Components\TextEntry::make('website')
                                    ->label('Website')
                                    ->url(fn ($record) => $record->website)
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-o-link')
                                    ->placeholder('No website provided'),
                            ]),

                            Infolists\Components\Group::make([
                                Infolists\Components\ImageEntry::make('logo')
                                    ->label('Brand Logo')
                                    ->size(200)
                                    ->placeholder('/images/default-brand-logo.png'),

                                Infolists\Components\ColorEntry::make('brand_color')
                                    ->label('Brand Color'),
                            ]),
                        ])->from('md'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Brand Configuration')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Group::make([
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Active Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-badge')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                Infolists\Components\IconEntry::make('featured')
                                    ->label('Featured Brand')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-star')
                                    ->falseIcon('heroicon-o-star')
                                    ->trueColor('warning')
                                    ->falseColor('gray'),
                            ]),

                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('tier')
                                    ->label('Brand Tier')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'premium' => 'success',
                                        'standard' => 'primary',
                                        'basic' => 'warning',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('sort_order')
                                    ->label('Display Order')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                        ])->from('md'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Server Statistics')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('servers_count')
                                    ->label('Total Servers')
                                    ->numeric()
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('success')
                                    ->state(fn ($record) => $record->servers()->count()),

                                Infolists\Components\TextEntry::make('active_servers_count')
                                    ->label('Active Servers')
                                    ->numeric()
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->state(fn ($record) => $record->servers()->where('is_active', true)->count()),
                            ]),

                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('online_servers_count')
                                    ->label('Online Servers')
                                    ->numeric()
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('success')
                                    ->state(fn ($record) => $record->servers()->where('status', 'up')->count()),

                                Infolists\Components\TextEntry::make('total_clients')
                                    ->label('Total Clients')
                                    ->numeric()
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('warning')
                                    ->state(fn ($record) => $record->servers()->sum('total_clients')),
                            ]),
                        ])->from('md'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime()
                            ->since(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->since(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}

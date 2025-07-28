<?php

namespace App\Filament\Concerns;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

trait HasPerformanceOptimizations
{
    /**
     * Apply performance optimizations to table queries
     */
    protected function optimizeTableQuery(Builder $query): Builder
    {
        // Eager load commonly accessed relationships
        return $query->with($this->getEagerLoadedRelations());
    }

    /**
     * Get relationships that should be eager loaded
     */
    protected function getEagerLoadedRelations(): array
    {
        return [];
    }

    /**
     * Apply performance-optimized pagination
     */
    protected function applyPerformancePagination(Table $table): Table
    {
        return $table
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([25, 50, 100])
            ->deferLoading()
            ->striped()
            ->extremePaginationLinks();
    }

    /**
     * Configure lazy loading for large datasets
     */
    protected function configureLazyLoading(Table $table): Table
    {
        // Use deferLoading() for async loading; loadingIndicatorPosition is not available in Filament Table.
        // If you want to customize the loading indicator, use loadingIndicator() if supported by your Filament version.
        // Example: ->loadingIndicator('Loading users...')
        return $table
            ->deferLoading()
            // ->loadingIndicator('Loading users...') // Uncomment if your Filament version supports it
            ->emptyStateHeading('No records found')
            ->emptyStateDescription('Try adjusting your filters or search criteria.')
            ->emptyStateIcon('heroicon-o-magnifying-glass');
    }

    /**
     * Add performance filters for common queries
     */
    protected function addPerformanceFilters(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession();
    }

    /**
     * Configure bulk actions with progress indicators
     */
    protected function configureBulkActions(Table $table): Table
    {
        return $table
            ->bulkActions([
                // Bulk actions will be added in implementing classes
            ])
            ->selectCurrentPageOnly()
            ->deselectAllRecordsWhenFiltered();
    }

    /**
     * Add caching for expensive computed columns
     */
    protected function addCachedColumns(): array
    {
        return [
            // Override in implementing classes to add cached columns
        ];
    }

    /**
     * Configure search optimization
     */
    protected function optimizeSearch(Table $table): Table
    {
        return $table
            ->searchOnBlur()
            ->searchDebounce('500ms')
            ->persistSearchInSession();
    }

    /**
     * Apply all performance optimizations to a table
     */
    protected function applyAllPerformanceOptimizations(Table $table): Table
    {
        // Refactored to remove ->let() calls, which do not exist in Filament Table.
        $table = $this->applyPerformancePagination($table);
        $table = $this->configureLazyLoading($table);
        $table = $this->addPerformanceFilters($table);
        $table = $this->configureBulkActions($table);
        $table = $this->optimizeSearch($table);
        return $table;
    }
}

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
        return $table
            ->deferLoading()
            ->loadingIndicatorPosition('bottom')
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
            ->deselectRecordsAfterCompletion();
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
        return $this->applyPerformancePagination($table)
            ->let(fn($table) => $this->configureLazyLoading($table))
            ->let(fn($table) => $this->addPerformanceFilters($table))
            ->let(fn($table) => $this->configureBulkActions($table))
            ->let(fn($table) => $this->optimizeSearch($table));
    }
}

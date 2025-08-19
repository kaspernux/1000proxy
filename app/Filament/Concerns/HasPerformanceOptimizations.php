<?php

namespace App\Filament\Concerns;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

trait HasPerformanceOptimizations
{
    /**
     * Apply a unified UI + performance preset to a Filament Table builder.
     * Options:
     * - defaultPage: int (25)
     * - pageOptions: int[] ([25,50,100])
     * - compact: bool (true) applies 'fi-compact' row density
     * - striped: bool (true)
     * - extremeLinks: bool (true) show first/last pagination links when supported
     * - empty: array{icon?: string, heading?: string, description?: string}
     */
    public static function applyTablePreset(Table $table, array $options = []): Table
    {
        $defaultPage = $options['defaultPage'] ?? 25;
        $pageOptions = $options['pageOptions'] ?? [25, 50, 100];
        $compact = $options['compact'] ?? true;
        $striped = $options['striped'] ?? true;
        $extremeLinks = $options['extremeLinks'] ?? true;
        $empty = $options['empty'] ?? [];

        // Pagination options (support both APIs depending on Filament version)
        if (method_exists($table, 'paginated')) {
            $table = $table->paginated($pageOptions);
        }
        if (method_exists($table, 'defaultPaginationPageOption')) {
            $table = $table->defaultPaginationPageOption($defaultPage);
        }
        if (method_exists($table, 'paginationPageOptions')) {
            $table = $table->paginationPageOptions($pageOptions);
        }

        // Defer initial load where available for large datasets (skip in tests)
        if (method_exists($table, 'deferLoading') && !app()->environment('testing')) {
            $table = $table->deferLoading();
        }

        // Density preset
        if ($compact && method_exists($table, 'recordClasses')) {
            $table = $table->recordClasses(fn () => 'fi-compact');
        }

        // Row styling
        if ($striped && method_exists($table, 'striped')) {
            $table = $table->striped();
        }

        // Empty state defaults (customizable per resource)
        if (!empty($empty)) {
            if (!empty($empty['icon']) && method_exists($table, 'emptyStateIcon')) {
                $table = $table->emptyStateIcon($empty['icon']);
            }
            if (!empty($empty['heading']) && method_exists($table, 'emptyStateHeading')) {
                $table = $table->emptyStateHeading($empty['heading']);
            }
            if (!empty($empty['description']) && method_exists($table, 'emptyStateDescription')) {
                $table = $table->emptyStateDescription($empty['description']);
            }
        } else {
            // Generic fallback
            if (method_exists($table, 'emptyStateHeading')) {
                $table = $table->emptyStateHeading('No records found');
            }
            if (method_exists($table, 'emptyStateDescription')) {
                $table = $table->emptyStateDescription('Try adjusting your filters or search criteria.');
            }
            if (method_exists($table, 'emptyStateIcon')) {
                $table = $table->emptyStateIcon('heroicon-o-magnifying-glass');
            }
        }

        // First/last pagination links
        if ($extremeLinks && method_exists($table, 'extremePaginationLinks')) {
            $table = $table->extremePaginationLinks();
        }

        return $table;
    }

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
        // Apply pagination options supported by current Filament version
        if (method_exists($table, 'paginated')) {
            $table = $table->paginated([25, 50, 100]);
        }
        if (method_exists($table, 'defaultPaginationPageOption')) {
            $table = $table->defaultPaginationPageOption(25);
        }
        if (method_exists($table, 'paginationPageOptions')) {
            $table = $table->paginationPageOptions([25, 50, 100]);
        }

        // Optional methods guarded by existence checks
        if (method_exists($table, 'deferLoading')) {
            $table = $table->deferLoading();
        }
        if (method_exists($table, 'striped')) {
            $table = $table->striped();
        }
        if (method_exists($table, 'extremePaginationLinks')) {
            $table = $table->extremePaginationLinks();
        }

        return $table;
    }

    /**
     * Configure lazy loading for large datasets
     */
    protected function configureLazyLoading(Table $table): Table
    {
        // Use deferLoading() for async loading when available (skip in tests)
        if (method_exists($table, 'deferLoading') && !app()->environment('testing')) {
            $table = $table->deferLoading();
        }

        // Customize empty state (supported across versions)
        return $table
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
        // Guard all calls for cross-version compatibility
        if (method_exists($table, 'persistFiltersInSession')) {
            $table = $table->persistFiltersInSession();
        }
        if (method_exists($table, 'persistSearchInSession')) {
            $table = $table->persistSearchInSession();
        }
        if (method_exists($table, 'persistSortInSession')) {
            $table = $table->persistSortInSession();
        }
        if (method_exists($table, 'persistColumnSearchesInSession')) {
            $table = $table->persistColumnSearchesInSession();
        }

        return $table;
    }

    /**
     * Configure bulk actions with progress indicators
     */
    protected function configureBulkActions(Table $table): Table
    {
        $table = $table->bulkActions([
            // Bulk actions will be added in implementing classes
        ]);

        if (method_exists($table, 'selectCurrentPageOnly')) {
            $table = $table->selectCurrentPageOnly();
        }
        if (method_exists($table, 'deselectAllRecordsWhenFiltered')) {
            $table = $table->deselectAllRecordsWhenFiltered();
        }

        return $table;
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
        if (method_exists($table, 'searchOnBlur')) {
            $table = $table->searchOnBlur();
        }
        if (method_exists($table, 'searchDebounce')) {
            $table = $table->searchDebounce('500ms');
        }

        return $table;
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

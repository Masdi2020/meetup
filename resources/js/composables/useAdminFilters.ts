import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { watch } from 'vue';
import type { WatchSource } from 'vue';

type FilterValue = string | number | null | undefined;
type FilterQuery = Record<string, FilterValue>;

interface AdminFilterOptions {
    debouncedSources?: WatchSource[];
    instantSources?: WatchSource[];
    query: () => FilterQuery;
    reset: () => void;
    debounce?: number;
}

function cleanQuery(query: FilterQuery): Record<string, string | number> {
    return Object.fromEntries(
        Object.entries(query).flatMap(([key, value]) => {
            if (value === null || value === undefined) {
                return [];
            }

            const normalized = typeof value === 'string' ? value.trim() : value;

            return normalized === '' ? [] : [[key, normalized]];
        }),
    );
}

export function useAdminFilters(path: string, options: AdminFilterOptions) {
    let lastFilterKey = JSON.stringify(cleanQuery(options.query()));

    function visit(query: Record<string, string | number>) {
        router.get(path, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function applyFilters() {
        const query = cleanQuery(options.query());
        const filterKey = JSON.stringify(query);

        if (filterKey === lastFilterKey) {
            return;
        }

        lastFilterKey = filterKey;
        visit(query);
    }

    function resetFilters() {
        options.reset();
        applyFilters();
    }

    function goToPage(page: number) {
        visit(cleanQuery({ ...options.query(), page }));
    }

    if (options.debouncedSources?.length) {
        watchDebounced(options.debouncedSources, applyFilters, {
            debounce: options.debounce ?? 500,
            maxWait: 1500,
        });
    }

    if (options.instantSources?.length) {
        watch(options.instantSources, applyFilters);
    }

    return {
        applyFilters,
        resetFilters,
        goToPage,
    };
}

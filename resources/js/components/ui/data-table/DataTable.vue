<script setup lang="ts" generic="TData, TValue">
import type {
    ColumnDef,
    ColumnFiltersState,
    SortingState,
    VisibilityState,
} from '@tanstack/vue-table';
import {
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { ref } from 'vue';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table/index';
import { ChevronDown } from 'lucide-vue-next';

interface DataTableProps {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    filterColumn?: string; // ← NUEVO: prop opcional para especificar qué columna filtrar
    filterPlaceholder?: string; // ← NUEVO: placeholder personalizado
}

const props = withDefaults(defineProps<DataTableProps>(), {
    filterColumn: undefined,
    filterPlaceholder: 'Buscar...',
});

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: (updaterOrValue) => {
        sorting.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(sorting.value)
                : updaterOrValue;
    },
    onColumnFiltersChange: (updaterOrValue) => {
        columnFilters.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(columnFilters.value)
                : updaterOrValue;
    },
    onColumnVisibilityChange: (updaterOrValue) => {
        columnVisibility.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(columnVisibility.value)
                : updaterOrValue;
    },
    onRowSelectionChange: (updaterOrValue) => {
        rowSelection.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(rowSelection.value)
                : updaterOrValue;
    },
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
    },
});
</script>

<template>
    <div class="w-full">
        <!-- Input de filtro - solo se muestra si filterColumn está definido -->
        <div v-if="filterColumn" class="flex items-center gap-4 py-4">
            <Input
                :placeholder="filterPlaceholder"
                :model-value="
                    (table.getColumn(filterColumn)?.getFilterValue() as string) ?? ''
                "
                @update:model-value="
                    table.getColumn(filterColumn)?.setFilterValue($event)
                "
                class="max-w-sm"
            />
        </div>
        <div class="overflow-hidden rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                        >
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            :data-state="
                                row.getIsSelected() ? 'selected' : undefined
                            "
                        >
                            <TableCell
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                            >
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell
                                :colspan="table.getAllColumns().length"
                                class="h-24 text-center text-muted-foreground"
                            >
                                No se encontraron resultados.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>
        <div class="flex items-center justify-end space-x-2 py-4">
            <div class="text-muted-foreground flex-1 text-sm">
                {{ table.getFilteredSelectedRowModel().rows.length }} de
                {{ table.getFilteredRowModel().rows.length }} fila(s)
                seleccionada(s).
            </div>
            <div class="space-x-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()"
                >
                    Anterior
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!table.getCanNextPage()"
                    @click="table.nextPage()"
                >
                    Siguiente
                </Button>
            </div>
        </div>
    </div>
</template>
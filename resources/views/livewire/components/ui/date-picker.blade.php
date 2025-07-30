{{-- Advanced Date Picker Component Template --}}
@php
    $name = $name ?? 'date';
    $value = $value ?? '';
    $format = $format ?? 'Y-m-d';
    $displayFormat = $displayFormat ?? 'M j, Y';
    $mode = $mode ?? 'single'; // single, range, multiple
    $enableTime = $enableTime ?? false;
    $timeFormat = $timeFormat ?? 'H:i';
    $minDate = $minDate ?? null;
    $maxDate = $maxDate ?? null;
    $disabledDates = $disabledDates ?? [];
    $locale = $locale ?? 'en';
    $placeholder = $placeholder ?? 'Select date';
    $size = $size ?? 'md'; // sm, md, lg
    $variant = $variant ?? 'default'; // default, minimal, inline
@endphp

<div
    x-data="datePicker()"
    x-init="
        name = '{{ $name }}';
        value = '{{ $value }}';
        format = '{{ $format }}';
        displayFormat = '{{ $displayFormat }}';
        mode = '{{ $mode }}';
        enableTime = {{ $enableTime ? 'true' : 'false' }};
        timeFormat = '{{ $timeFormat }}';
        minDate = {{ $minDate ? "'" . $minDate . "'" : 'null' }};
        maxDate = {{ $maxDate ? "'" . $maxDate . "'" : 'null' }};
        disabledDates = @json($disabledDates);
        locale = '{{ $locale }}';
        placeholder = '{{ $placeholder }}';
        size = '{{ $size }}';
        variant = '{{ $variant }}';
        init();
    "
    {{ $attributes->merge(['class' => 'relative']) }}
>
    {{-- Input Field --}}
    <div x-show="variant !== 'inline'" class="relative">
        <input
            x-ref="input"
            type="text"
            :name="name"
            :value="getDisplayValue()"
            :placeholder="placeholder"
            @click="toggle()"
            @keydown.escape="close()"
            @keydown.tab="close()"
            @keydown.enter.prevent="selectToday()"
            readonly
            :class="getInputClasses()"
            class="w-full border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
        />

        {{-- Calendar Icon --}}
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>

        {{-- Clear Button --}}
        <button
            x-show="hasValue() && !isReadonly"
            @click.stop="clear()"
            type="button"
            class="absolute inset-y-0 right-8 flex items-center pr-1 text-gray-400 hover:text-gray-600 focus:outline-none"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Hidden Input for Form Submission --}}
    <input type="hidden" :name="name" :value="getFormValue()" />

    {{-- Dropdown Calendar --}}
    <div
        x-show="isOpen || variant === 'inline'"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        @click.away="close()"
        :class="getCalendarClasses()"
        class="bg-white border border-gray-200 rounded-lg shadow-lg z-50"
    >
        {{-- Calendar Header --}}
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            {{-- Previous Year --}}
            <button
                @click="previousYear()"
                type="button"
                class="p-1 hover:bg-gray-100 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                title="Previous year"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
            </button>

            {{-- Previous Month --}}
            <button
                @click="previousMonth()"
                type="button"
                class="p-1 hover:bg-gray-100 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                title="Previous month"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            {{-- Month/Year Display --}}
            <div class="flex items-center gap-2">
                <button
                    @click="showMonthPicker = !showMonthPicker"
                    type="button"
                    class="text-lg font-semibold hover:bg-gray-100 px-2 py-1 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    x-text="getMonthName(currentMonth)"
                ></button>
                <button
                    @click="showYearPicker = !showYearPicker"
                    type="button"
                    class="text-lg font-semibold hover:bg-gray-100 px-2 py-1 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    x-text="currentYear"
                ></button>
            </div>

            {{-- Next Month --}}
            <button
                @click="nextMonth()"
                type="button"
                class="p-1 hover:bg-gray-100 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                title="Next month"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            {{-- Next Year --}}
            <button
                @click="nextYear()"
                type="button"
                class="p-1 hover:bg-gray-100 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                title="Next year"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        {{-- Month Picker --}}
        <div x-show="showMonthPicker" class="p-4">
            <div class="grid grid-cols-3 gap-2">
                <template x-for="(month, index) in getMonthNames()" :key="index">
                    <button
                        @click="selectMonth(index)"
                        type="button"
                        :class="getMonthButtonClasses(index)"
                        class="p-2 text-sm rounded hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        x-text="month"
                    ></button>
                </template>
            </div>
        </div>

        {{-- Year Picker --}}
        <div x-show="showYearPicker" class="p-4 max-h-64 overflow-y-auto">
            <div class="grid grid-cols-4 gap-2">
                <template x-for="year in getYearRange()" :key="year">
                    <button
                        @click="selectYear(year)"
                        type="button"
                        :class="getYearButtonClasses(year)"
                        class="p-2 text-sm rounded hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        x-text="year"
                    ></button>
                </template>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div x-show="!showMonthPicker && !showYearPicker" class="p-4">
            {{-- Day Headers --}}
            <div class="grid grid-cols-7 gap-1 mb-2">
                <template x-for="day in getDayNames()" :key="day">
                    <div class="p-2 text-xs font-medium text-gray-500 text-center" x-text="day"></div>
                </template>
            </div>

            {{-- Calendar Days --}}
            <div class="grid grid-cols-7 gap-1">
                <template x-for="(day, index) in getCalendarDays()" :key="index">
                    <button
                        @click="selectDate(day)"
                        type="button"
                        :disabled="isDateDisabled(day)"
                        :class="getDayButtonClasses(day)"
                        class="p-2 text-sm rounded hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-text="day ? day.getDate() : ''"></span>

                        {{-- Range Selection Indicators --}}
                        <div x-show="mode === 'range' && isInRange(day)" class="absolute inset-0 bg-blue-100 -z-10"></div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Time Picker --}}
        <div x-show="enableTime && !showMonthPicker && !showYearPicker" class="p-4 border-t border-gray-200">
            <div class="flex items-center justify-center gap-2">
                {{-- Hour Input --}}
                <div class="flex items-center">
                    <label class="text-sm font-medium text-gray-700 mr-2">Hour:</label>
                    <select
                        x-model="selectedHour"
                        @change="updateTime()"
                        class="border border-gray-300 rounded px-2 py-1 text-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <template x-for="hour in getHours()" :key="hour">
                            <option :value="hour" x-text="hour.toString().padStart(2, '0')"></option>
                        </template>
                    </select>
                </div>

                <span class="text-gray-500">:</span>

                {{-- Minute Input --}}
                <div class="flex items-center">
                    <label class="text-sm font-medium text-gray-700 mr-2">Min:</label>
                    <select
                        x-model="selectedMinute"
                        @change="updateTime()"
                        class="border border-gray-300 rounded px-2 py-1 text-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <template x-for="minute in getMinutes()" :key="minute">
                            <option :value="minute" x-text="minute.toString().padStart(2, '0')"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        {{-- Calendar Footer --}}
        <div class="flex items-center justify-between p-4 border-t border-gray-200 bg-gray-50">
            {{-- Quick Actions --}}
            <div class="flex items-center gap-2">
                <button
                    @click="selectToday()"
                    type="button"
                    class="text-sm text-blue-600 hover:text-blue-800 focus:outline-none focus:underline"
                >
                    Today
                </button>
                <button
                    x-show="mode === 'range'"
                    @click="selectThisWeek()"
                    type="button"
                    class="text-sm text-blue-600 hover:text-blue-800 focus:outline-none focus:underline"
                >
                    This Week
                </button>
                <button
                    x-show="mode === 'range'"
                    @click="selectThisMonth()"
                    type="button"
                    class="text-sm text-blue-600 hover:text-blue-800 focus:outline-none focus:underline"
                >
                    This Month
                </button>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2">
                <button
                    @click="clear()"
                    type="button"
                    class="px-3 py-1 text-sm text-gray-600 border border-gray-300 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Clear
                </button>
                <button
                    x-show="variant !== 'inline'"
                    @click="close()"
                    type="button"
                    class="px-3 py-1 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Done
                </button>
            </div>
        </div>
    </div>

    {{-- Selected Date Display (for range/multiple modes) --}}
    <div x-show="mode !== 'single' && hasValue()" class="mt-2">
        <div class="text-sm text-gray-600">
            <span x-show="mode === 'range'">
                Selected range: <span class="font-medium" x-text="getRangeDisplayText()"></span>
            </span>
            <span x-show="mode === 'multiple'">
                Selected dates (<span x-text="selectedDates.length"></span>):
            </span>
        </div>

        {{-- Multiple Dates Display --}}
        <div x-show="mode === 'multiple'" class="flex flex-wrap gap-1 mt-1">
            <template x-for="(date, index) in selectedDates" :key="index">
                <span class="inline-flex items-center px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                    <span x-text="formatDate(date, displayFormat)"></span>
                    <button
                        @click="removeDate(index)"
                        type="button"
                        class="ml-1 hover:text-blue-600 focus:outline-none"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </span>
            </template>
        </div>
    </div>
</div>

{{-- Example Usage in Comments:
<!--
<!-- Basic Date Picker -->
<x-ui.date-picker
    name="date"
    placeholder="Select a date"
    format="Y-m-d"
    display-format="M j, Y"
/>

<!-- Date Range Picker -->
<x-ui.date-picker
    mode="range"
    name="date_range"
    placeholder="Select date range"
/>

<!-- Date Time Picker -->
<x-ui.date-picker
    name="datetime"
    :enable-time="true"
    format="Y-m-d H:i"
    placeholder="Select date and time"
/>

<!-- Multiple Dates -->
<x-ui.date-picker
    mode="multiple"
    name="dates"
    placeholder="Select multiple dates"
    :max-date="now()->addMonths(6)->format('Y-m-d')"
/>

<!-- Inline Calendar -->
<x-ui.date-picker
    variant="inline"
    name="calendar"
    :min-date="now()->format('Y-m-d')"
/>
-->
--}}

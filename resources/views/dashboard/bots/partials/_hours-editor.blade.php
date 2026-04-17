{{--
    Weekly hours editor — reusable Alpine partial.

    Exposed Alpine data in the enclosing scope (expected keys):
      days          : array<int, { label: string, open: string, close: string, closed: bool }>
      toggleAllClosed(bool)
      copyToAll()

    Expected parent component contract:
      - `days` is an array of exactly 7 entries, index 0..6 = Mon..Sun
        (ISO-8601 weekday 1..7 minus one).
      - Each entry carries its own UI-localised label so this partial
        doesn't depend on a shared dictionary.

    The partial does NOT render <input name="..."> fields. Serialisation
    is the parent's responsibility (the booking hours page reads the
    day objects at form submit and builds the `staff[id][weekday]`
    payload — see _hours-editor usage in booking/index.blade.php).
--}}
<div class="border border-slate-200 rounded-lg divide-y divide-slate-100 bg-slate-50/30">
    <template x-for="(day, idx) in days" :key="idx">
        <div class="grid grid-cols-12 gap-2 items-center px-3 py-2.5">
            <div class="col-span-12 sm:col-span-2 text-sm font-medium text-slate-700" x-text="day.label"></div>
            <label class="col-span-6 sm:col-span-2 flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" x-model="day.closed" class="rounded border-slate-300 text-red-700 focus:ring-red-700/20">
                <span>Închis</span>
            </label>
            <div class="col-span-3 sm:col-span-3">
                <input type="time" x-model="day.open" :disabled="day.closed"
                       class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs disabled:bg-slate-100 disabled:text-slate-400">
            </div>
            <div class="col-span-3 sm:col-span-3">
                <input type="time" x-model="day.close" :disabled="day.closed"
                       class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs disabled:bg-slate-100 disabled:text-slate-400">
            </div>
            <div class="col-span-12 sm:col-span-2 text-right">
                <span class="text-xs text-slate-400" x-show="!day.closed" x-text="day.open + ' - ' + day.close"></span>
            </div>
        </div>
    </template>
</div>
<div class="flex flex-wrap gap-2 mt-2">
    <button type="button" @click="copyMonToWeekdays && copyMonToWeekdays()"
            x-show="typeof copyMonToWeekdays === 'function'"
            class="text-xs px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50 text-slate-700">
        Copiază Luni pe L-V
    </button>
    <button type="button" @click="markAllClosed && markAllClosed(true)"
            x-show="typeof markAllClosed === 'function'"
            class="text-xs px-3 py-1.5 rounded-md border border-slate-300 hover:bg-slate-50 text-slate-700">
        Weekend închis
    </button>
</div>

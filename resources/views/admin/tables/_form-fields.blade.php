{{--
    Shared by create.blade.php and edit.blade.php. $columns is the enriched
    column metadata from AdminRecordController::editableColumns() — each has
    name/type_name/nullable/enum_options/is_boolean. $values (optional) is the
    existing row when editing.
--}}
@foreach($columns as $column)
    @php
        $name = $column['name'];
        $current = (isset($values) && $values !== null) ? ($values->{$name} ?? null) : null;
    @endphp
    <div>
        <label class="mb-1.5 block text-xs font-semibold text-novix-muted">
            {{ Str::headline($name) }}
            <span class="font-normal lowercase text-novix-muted/70">({{ $column['type_name'] }}{{ $column['nullable'] ? ', optional' : '' }})</span>
        </label>

        @if(!empty($column['enum_options']))
            <select name="{{ $name }}" class="w-full rounded-xl border border-gray-200 bg-novix-cream/40 px-4 py-2.5 text-sm text-novix-ink shadow-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">
                @if($column['nullable'])
                    <option value="" {{ $current === null ? 'selected' : '' }}>—</option>
                @endif
                @foreach($column['enum_options'] as $option)
                    <option value="{{ $option }}" {{ (string) $current === $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
        @elseif($column['is_boolean'])
            <select name="{{ $name }}" class="w-full rounded-xl border border-gray-200 bg-novix-cream/40 px-4 py-2.5 text-sm text-novix-ink shadow-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">
                <option value="1" {{ $current ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$current ? 'selected' : '' }}>No</option>
            </select>
        @elseif(in_array($column['type_name'], ['text', 'longtext', 'mediumtext', 'json']))
            <textarea name="{{ $name }}" rows="4" class="w-full rounded-xl border border-gray-200 bg-novix-cream/40 px-4 py-2.5 text-sm text-novix-ink shadow-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">{{ old($name, $current) }}</textarea>
        @else
            <input type="text" name="{{ $name }}" value="{{ old($name, $current) }}"
                class="w-full rounded-xl border border-gray-200 bg-novix-cream/40 px-4 py-2.5 text-sm text-novix-ink shadow-sm focus:border-novix-green focus:outline-none focus:ring-2 focus:ring-novix-green/30">
        @endif

        @error($name)
            <p class="mt-1 text-xs font-semibold text-novix-pink-dark">{{ $message }}</p>
        @enderror
    </div>
@endforeach

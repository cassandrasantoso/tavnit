@csrf

<div>
    <label for="name">{{ __('spots.form.name') }}</label>
    <input id="name" name="name" value="{{ old('name', $spot->name) }}" maxlength="100" required>
    @error('name') <p>{{ $message }}</p> @enderror
</div>

<div>
    <label for="name_kana">{{ __('spots.form.name_kana') }}</label>
    <input id="name_kana" name="name_kana" value="{{ old('name_kana', $spot->name_kana) }}" maxlength="100" lang="ja">
    @error('name_kana') <p>{{ $message }}</p> @enderror
</div>

<div>
    <label for="name_en">{{ __('spots.form.name_en') }}</label>
    <input id="name_en" name="name_en" value="{{ old('name_en', $spot->name_en) }}" maxlength="100" lang="en">
    @error('name_en') <p>{{ $message }}</p> @enderror
</div>

<div>
    <label for="description_ja">{{ __('spots.form.description') }}</label>
    <textarea id="description_ja" name="description_ja" rows="4">{{ old('description_ja', $spot->description_ja) }}</textarea>
    @error('description_ja') <p>{{ $message }}</p> @enderror
</div>

<div>
    <label for="latitude">{{ __('spots.form.latitude') }}</label>
    <input id="latitude" name="latitude" type="number" step="0.000001" value="{{ old('latitude', $spot->latitude) }}" required>
    @error('latitude') <p>{{ $message }}</p> @enderror
</div>

<div>
    <label for="longitude">{{ __('spots.form.longitude') }}</label>
    <input id="longitude" name="longitude" type="number" step="0.000001" value="{{ old('longitude', $spot->longitude) }}" required>
    @error('longitude') <p>{{ $message }}</p> @enderror
</div>

<div>
    {{-- An unticked checkbox sends nothing, so this hidden 0 goes instead. --}}
    <input type="hidden" name="is_active" value="0">
    <label>
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $spot->is_active))>
        {{ __('spots.form.show_in_app') }}
    </label>
</div>

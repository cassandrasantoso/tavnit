@csrf

<div>
    <label for="name">Name</label>
    <input id="name" name="name" value="{{ old('name', $spot->name) }}" maxlength="100" required>
    @error('name') <p>{{ $message }}</p> @enderror
</div>

<div>
    <label for="description_ja">Description (Japanese)</label>
    <textarea id="description_ja" name="description_ja" rows="4">{{ old('description_ja', $spot->description_ja) }}</textarea>
    @error('description_ja') <p>{{ $message }}</p> @enderror
</div>

<div>
    <label for="latitude">Latitude</label>
    <input id="latitude" name="latitude" type="number" step="0.000001" value="{{ old('latitude', $spot->latitude) }}" required>
    @error('latitude') <p>{{ $message }}</p> @enderror
</div>

<div>
    <label for="longitude">Longitude</label>
    <input id="longitude" name="longitude" type="number" step="0.000001" value="{{ old('longitude', $spot->longitude) }}" required>
    @error('longitude') <p>{{ $message }}</p> @enderror
</div>

<div>
    {{-- An unticked checkbox sends nothing, so this hidden 0 goes instead. --}}
    <input type="hidden" name="is_active" value="0">
    <label>
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $spot->is_active))>
        Show in the app
    </label>
</div>

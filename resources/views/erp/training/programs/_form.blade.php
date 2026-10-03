@php $program ??= null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700">Program Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $program->name ?? '') }}" required  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="program_type" class="block text-sm font-medium text-gray-700">Program Type</label>
        <select name="program_type" id="program_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @foreach (\App\Services\MasterData::options('program_type', $program->program_type ?? null) as $value => $label)
                <option value="{{ $value }}" @selected(old('program_type', $program->program_type ?? 'learning') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('program_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="training_category_id" class="block text-sm font-medium text-gray-700">Category</label>
        <select name="training_category_id" id="training_category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            <option value="">No category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('training_category_id', $program->training_category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Groups this program for customers, e.g. "Excel Basic" under "Data Analyst".</p>
        @error('training_category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    @if (\App\Models\CertificateTemplate::exists())
        <div>
            <label for="certificate_template_id" class="block text-sm font-medium text-gray-700">Certificate template</label>
            <select name="certificate_template_id" id="certificate_template_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <option value="">Default template</option>
                @foreach (\App\Models\CertificateTemplate::orderBy('name')->get() as $template)
                    <option value="{{ $template->id }}" @selected((string) old('certificate_template_id', $program->certificate_template_id ?? '') === (string) $template->id)>{{ $template->name }}</option>
                @endforeach
            </select>
            @error('certificate_template_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endif
    <div class="">
        <label for="session_minutes" class="block text-sm font-medium text-gray-700">Session length (minutes)</label>
        <input type="number" name="session_minutes" id="session_minutes" value="{{ old('session_minutes', $program->session_minutes ?? '') }}" min="15" max="720" step="5" placeholder="e.g. 60" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        <p class="mt-1 text-xs text-gray-500">Customers choose any start time inside the operating hours; the end time follows from this length. Leave empty to book the whole operating-hours slot as it is.</p>
        @error('session_minutes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="duration_days" class="block text-sm font-medium text-gray-700">Number of meetings</label>
        <input type="number" name="duration_days" id="duration_days" value="{{ old('duration_days', $program->duration_days ?? 1) }}" required min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        <p class="mt-1 text-xs text-gray-500">Customers pick a date and time slot for exactly this many meetings when they register.</p>
        @error('duration_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="">
        <label for="standard_price" class="block text-sm font-medium text-gray-700">Standard Price (Rp)</label>
        <input type="number" name="standard_price" id="standard_price" value="{{ old('standard_price', $program->standard_price ?? 0) }}" required step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
        @error('standard_price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" id="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('description', $program->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="sm:col-span-2">
        <label for="terms" class="block text-sm font-medium text-gray-700">Terms &amp; Conditions</label>
        <textarea name="terms" id="terms" rows="4" placeholder="e.g. cancellation policy, attendance requirements, materials/refund rules…" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">{{ old('terms', $program->terms ?? '') }}</textarea>
        <p class="mt-1 text-xs text-gray-500">Shown to the customer for this program during registration; they must agree to it before submitting.</p>
        @error('terms') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $program->is_active ?? true)) class="rounded border-gray-300 text-brand focus:ring-brand">
        <label for="is_active" class="text-sm text-gray-700">Active</label>
    </div>
    <div class="sm:col-span-2">
        <div class="flex items-center gap-2">
            <input type="hidden" name="is_corporate" value="0">
            <input type="checkbox" name="is_corporate" id="is_corporate" value="1" @checked(old('is_corporate', $program->is_corporate ?? false)) class="rounded border-gray-300 text-brand focus:ring-brand">
            <label for="is_corporate" class="text-sm text-gray-700">Corporate training</label>
        </div>
        <p class="mt-1 text-xs text-gray-500">Unlocks the corporate-only operating-hours slots (Master Data → Operating Hours) for customers booking this program.</p>
    </div>

    <div class="sm:col-span-2 border-t border-gray-100 pt-5" x-data='{ tiers: @json(old("group_tiers", $program?->groupTiers() ?? [])) }'>
        <h3 class="text-sm font-semibold text-gray-800">Group Pricing</h3>
        <p class="mt-1 text-xs text-gray-500">Optional. Lets several individuals register together, each paying a lower price per person. The standard price above applies to 1 person; add a tier for every step where the price changes, e.g. from 2 people Rp 650.000, from 4 people Rp 500.000. Each person still gets their own customer ID.</p>
        <div class="mt-3 max-w-xs">
            <label for="group_max_size" class="block text-sm font-medium text-gray-700">Maximum people per group</label>
            <input type="number" name="group_max_size" id="group_max_size" min="1" max="50" value="{{ old('group_max_size', $program->group_max_size ?? '') }}" placeholder="e.g. 5 — empty = no groups" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
            @error('group_max_size') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="mt-3 space-y-2">
            <template x-for="(tier, i) in tiers" :key="i">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm text-gray-600">From</span>
                    <input type="number" min="2" :name="`group_tiers[${i}][min]`" x-model="tier.min" class="w-20 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    <span class="text-sm text-gray-600">people: Rp</span>
                    <input type="number" min="0" step="0.01" :name="`group_tiers[${i}][price]`" x-model="tier.price" class="w-36 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    <span class="text-sm text-gray-600">per person</span>
                    <button type="button" @click="tiers.splice(i, 1)" class="text-xs text-gray-400 hover:text-red-600">Remove</button>
                </div>
            </template>
        </div>
        <button type="button" @click="tiers.push({ min: '', price: '' })" class="mt-3 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">+ Add price tier</button>
        @error('group_tiers') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @foreach ($errors->get('group_tiers.*') as $messages)
            @foreach ($messages as $message) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @endforeach
        @endforeach
        <p class="mt-2 text-xs text-gray-400">A promo below is applied on top of the per-person price of each tier. Changes only affect new registrations.</p>
    </div>

    <div class="sm:col-span-2 border-t border-gray-100 pt-5">
        <h3 class="text-sm font-semibold text-gray-800">Illustration Photos</h3>
        <p class="mt-1 text-xs text-gray-500">Shown to customers while they choose this program. JPG, PNG, or WebP, max 2 MB each, up to 10 photos.</p>

        @if (($program->images ?? collect())->isNotEmpty())
            <div class="mt-3 grid grid-cols-3 sm:grid-cols-5 gap-3">
                @foreach ($program->images as $image)
                    <label class="group relative block cursor-pointer overflow-hidden rounded-md ring-1 ring-gray-200">
                        <img src="{{ $image->url() }}" alt="" class="h-24 w-full object-cover">
                        <span class="absolute inset-0 flex items-start justify-end bg-black/0 p-1 transition-colors group-has-[:checked]:bg-red-900/40">
                            <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="h-4 w-4 rounded border-white bg-white/80 text-red-600 focus:ring-red-500">
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-gray-400">Tick a photo to remove it when you save.</p>
        @endif

        <input type="file" name="images[]" multiple accept="image/png,image/jpeg,image/webp" class="mt-3 block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand hover:file:bg-red-100">
        @error('images') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('images.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2 border-t border-gray-100 pt-5">
        <h3 class="text-sm font-semibold text-gray-800">Promo / Discount</h3>
        <p class="mt-1 text-xs text-gray-500">Optional. Set this while a promo is running; customers see the discounted price until it expires.</p>
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label for="discount_type" class="block text-sm font-medium text-gray-700">Discount type</label>
                <select name="discount_type" id="discount_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                    <option value="" @selected(old('discount_type', $program->discount_type ?? '') === '')>No discount</option>
                    @foreach (\App\Models\TrainingProgram::DISCOUNT_TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('discount_type', $program->discount_type ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('discount_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="discount_value" id="discount_value_label" class="block text-sm font-medium text-gray-700">Discount</label>
                <input type="number" name="discount_value" id="discount_value" value="{{ old('discount_value', $program->discount_value ?? '') }}" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                @error('discount_value') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="discount_expires_at" class="block text-sm font-medium text-gray-700">Active until</label>
                <input type="date" name="discount_expires_at" id="discount_expires_at" value="{{ old('discount_expires_at', optional($program->discount_expires_at ?? null)->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand sm:text-sm">
                <p class="mt-1 text-xs text-gray-500">The promo ends at midnight after this date.</p>
                @error('discount_expires_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <p id="discount-preview" class="mt-2 text-sm text-green-700"></p>
    </div>
</div>

<script>
    (function () {
        const price = document.getElementById('standard_price');
        const type = document.getElementById('discount_type');
        const value = document.getElementById('discount_value');
        const valueLabel = document.getElementById('discount_value_label');
        const preview = document.getElementById('discount-preview');
        const fmt = new Intl.NumberFormat('id-ID');
        function update() {
            valueLabel.textContent = type.value === 'percentage' ? 'Discount (%)' : 'Discount (Rp)';
            value.max = type.value === 'percentage' ? 100 : '';
            const base = parseFloat(price.value) || 0;
            const v = parseFloat(value.value) || 0;
            if (!type.value || v <= 0 || !base) { preview.textContent = ''; return; }
            const amount = Math.min(type.value === 'percentage' ? base * v / 100 : v, base);
            preview.textContent = 'Rp ' + fmt.format(Math.round(amount)) + ' off — final price Rp ' + fmt.format(Math.round(base - amount));
        }
        price.addEventListener('input', update);
        type.addEventListener('change', update);
        value.addEventListener('input', update);
        update();
    })();
</script>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="inline-flex items-center rounded-md bg-gradient-to-br from-brand-light to-brand-dark px-4 py-2 text-sm font-medium text-white hover:from-brand-dark hover:to-brand-dark shadow-sm hover:shadow-md hover:-translate-y-px active:translate-y-0 transition-all duration-150">{{ $program ? 'Update Program' : 'Create Program' }}</button>
    <a href="{{ route('training.programs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>

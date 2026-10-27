@extends('layouts.app')

@section('title', __('Account Settings'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{
         activeSection: '{{ $activeSection ?? 'profile' }}',
         bioLength: {{ strlen($user->bio ?? '') }},
         username: '{{ $user->username }}',
         usernameChecking: false,
         usernameAvailable: null,
         usernameMsg: '',
         timeout: null,
         avatarPreview: '{{ $user->avatar_url }}',
         coverPreview: '{{ $user->cover_url }}',
         deleteModal: false,

         previewAvatar(event) {
             const file = event.target.files[0];
             if (file) {
                 this.avatarPreview = URL.createObjectURL(file);
             }
         },
         previewCover(event) {
             const file = event.target.files[0];
             if (file) {
                 this.coverPreview = URL.createObjectURL(file);
             }
         },
         checkUsernameDebounced() {
             clearTimeout(this.timeout);
             this.usernameChecking = true;
             this.usernameAvailable = null;
             this.timeout = setTimeout(() => {
                 fetch(`{{ route('ajax.profile.check-username') }}?username=${encodeURIComponent(this.username)}`)
                     .then(res => res.json())
                     .then(data => {
                         this.usernameChecking = false;
                         this.usernameAvailable = data.available;
                         this.usernameMsg = data.message;
                     })
                     .catch(() => {
                         this.usernameChecking = false;
                     });
             }, 400);
         }
     }">

    {{-- Breadcrumb / Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
            <a href="{{ $user->username ? route('profile.show', $user->username) : '#' }}" class="hover:text-primary transition-colors">{{ __('My Profile') }}</a>
            <span>/</span>
            <span class="text-gray-900">{{ __('Settings') }}</span>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ __('Account Settings') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('Manage your profile details, privacy preferences, and account security.') }}</p>
    </div>

    {{-- Layout: Sidebar + Form Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

        {{-- LEFT SIDEBAR NAVIGATION --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-3xl p-3 border border-gray-100 shadow-sm sticky top-24 space-y-1">
                <button type="button"
                        @click="activeSection = 'profile'"
                        :class="activeSection === 'profile' ? 'bg-primary/10 text-primary font-bold' : 'text-gray-600 hover:bg-gray-50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-all text-left">
                    <x-icon name="user" class="w-4 h-4 text-inherit" />
                    <span>{{ __('Profile Info') }}</span>
                </button>

                <button type="button"
                        @click="activeSection = 'preferences'"
                        :class="activeSection === 'preferences' ? 'bg-primary/10 text-primary font-bold' : 'text-gray-600 hover:bg-gray-50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-all text-left">
                    <x-icon name="compass" class="w-4 h-4 text-inherit" />
                    <span>{{ __('Travel Preferences') }}</span>
                </button>

                <button type="button"
                        @click="activeSection = 'security'"
                        :class="activeSection === 'security' ? 'bg-primary/10 text-primary font-bold' : 'text-gray-600 hover:bg-gray-50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-all text-left">
                    <x-icon name="lock" class="w-4 h-4 text-inherit" />
                    <span>{{ __('Security & Password') }}</span>
                </button>

                <button type="button"
                        @click="activeSection = 'privacy'"
                        :class="activeSection === 'privacy' ? 'bg-primary/10 text-primary font-bold' : 'text-gray-600 hover:bg-gray-50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-all text-left">
                    <x-icon name="shield" class="w-4 h-4 text-inherit" />
                    <span>{{ __('Privacy') }}</span>
                </button>

                <button type="button"
                        @click="activeSection = 'notifications'"
                        :class="activeSection === 'notifications' ? 'bg-primary/10 text-primary font-bold' : 'text-gray-600 hover:bg-gray-50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-all text-left">
                    <x-icon name="bell" class="w-4 h-4 text-inherit" />
                    <span>{{ __('Notifications') }}</span>
                </button>

                <button type="button"
                        @click="activeSection = 'danger'"
                        :class="activeSection === 'danger' ? 'bg-red-50 text-red-600 font-bold' : 'text-red-500 hover:bg-red-50/50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-all text-left pt-3 border-t border-gray-100">
                    <x-icon name="alert-triangle" class="w-4 h-4 text-inherit" />
                    <span>{{ __('Danger Zone') }}</span>
                </button>
            </div>
        </div>

        {{-- RIGHT MAIN CONTENT AREA --}}
        <div class="lg:col-span-3 space-y-6">

            {{-- SECTION 1: PROFILE INFO --}}
            <div x-show="activeSection === 'profile'" x-cloak class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <x-icon name="user" class="w-5 h-5 text-primary" /> {{ __('Public Profile') }}
                </h2>

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    {{-- Cover Photo Upload --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            {{ __('Cover Image (Max 4MB)') }}
                        </label>
                        <div class="relative h-40 rounded-2xl bg-gradient-to-r from-teal-600 to-amber-500 overflow-hidden border border-gray-200">
                            <template x-if="coverPreview">
                                <img :src="coverPreview" class="w-full h-full object-cover">
                            </template>
                            <div class="absolute inset-0 bg-black/30 flex items-center justify-center gap-3">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-white/90 text-gray-800 hover:bg-white shadow-md transition-all">
                                    <x-icon name="folder" class="w-4 h-4 text-gray-700" /> {{ __('Change Cover') }}
                                    <input type="file" name="cover_image" accept="image/*" class="hidden" @change="previewCover($event)">
                                </label>
                                @if($user->cover_image)
                                    <form action="{{ route('profile.cover.destroy') }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-red-600/90 text-white hover:bg-red-700 shadow-md transition-all">
                                            <x-icon name="trash-2" class="w-3.5 h-3.5 text-white" /> {{ __('Remove') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        @error('cover_image')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Avatar Upload --}}
                    <div class="flex items-center gap-5 pt-2">
                        <div class="relative w-20 h-20 rounded-2xl overflow-hidden ring-4 ring-gray-100 shadow-sm shrink-0">
                            <img :src="avatarPreview" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                {{ __('Avatar Photo (Max 2MB)') }}
                            </label>
                            <div class="flex items-center gap-2 mt-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                                    <x-icon name="camera" class="w-4 h-4 text-gray-700" /> {{ __('Upload Photo') }}
                                    <input type="file" name="avatar" accept="image/*" class="hidden" @change="previewAvatar($event)">
                                </label>
                                @if($user->avatar)
                                    <button type="submit" form="form-remove-avatar" class="px-3 py-2 rounded-xl text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">
                                        {{ __('Remove') }}
                                    </button>
                                @endif
                            </div>
                            @error('avatar')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Name & Username --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                {{ __('Full Name') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="80"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @error('name')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                {{ __('Username') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-2.5 text-gray-400 text-sm">@</span>
                                <input type="text" name="username" x-model="username" @input="checkUsernameDebounced()" required maxlength="30"
                                       class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <div class="mt-1 flex items-center gap-1.5 text-xs">
                                <template x-if="usernameChecking">
                                    <span class="text-gray-400 inline-flex items-center gap-1">
                                        <x-icon name="loader" class="w-3 h-3 animate-spin text-gray-400" />
                                        {{ __('Checking availability...') }}
                                    </span>
                                </template>
                                <template x-if="!usernameChecking && usernameAvailable === true">
                                    <span class="text-emerald-600 font-medium inline-flex items-center gap-1">
                                        <x-icon name="check" class="w-3.5 h-3.5 text-emerald-600" />
                                        <span x-text="usernameMsg"></span>
                                    </span>
                                </template>
                                <template x-if="!usernameChecking && usernameAvailable === false">
                                    <span class="text-red-500 font-medium inline-flex items-center gap-1">
                                        <x-icon name="x" class="w-3.5 h-3.5 text-red-500" />
                                        <span x-text="usernameMsg"></span>
                                    </span>
                                </template>
                            </div>
                            @error('username')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Bio --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                {{ __('Bio') }}
                            </label>
                            <span class="text-xs text-gray-400"><span x-text="bioLength"></span>/300</span>
                        </div>
                        <textarea name="bio" rows="3" maxlength="300" @input="bioLength = $el.value.length"
                                  placeholder="{{ __('Tell the community about your travel passions...') }}"
                                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Location, Phone, Website --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                {{ __('Location') }}
                            </label>
                            <input type="text" name="location" value="{{ old('location', $user->location) }}" placeholder="{{ __('Hanoi, Vietnam') }}" maxlength="120"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                {{ __('Phone Number') }}
                            </label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+84 90 123 4567" maxlength="30"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                {{ __('Website') }}
                            </label>
                            <input type="url" name="website" value="{{ old('website', $user->website) }}" placeholder="https://myblog.com"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>

                    {{-- Submit button --}}
                    <div class="pt-4 border-t border-gray-100 flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl text-sm font-semibold text-white bg-primary hover:bg-teal-700 shadow-lg shadow-primary/25 transition-all">
                            <x-icon name="check" class="w-4 h-4 text-white" /> {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>

                {{-- Hidden Form for Removing Avatar --}}
                <form id="form-remove-avatar" action="{{ route('profile.avatar.destroy') }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>

            {{-- SECTION 2: TRAVEL PREFERENCES --}}
            <div x-show="activeSection === 'preferences'" x-cloak class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <x-icon name="compass" class="w-5 h-5 text-primary" /> {{ __('Travel Preferences & Match Engine') }}
                </h2>

                <form method="POST" action="{{ route('profile.preferences.update') }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    {{-- Travel Pace --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            {{ __('Preferred Travel Pace') }}
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach([
                                'relaxed' => ['label' => __('Relaxed (1-2 POIs/day)'), 'icon' => 'tree-palm'],
                                'balanced' => ['label' => __('Balanced (3-4 POIs/day)'), 'icon' => 'scale'],
                                'packed' => ['label' => __('Action-Packed (5+ POIs/day)'), 'icon' => 'zap']
                            ] as $val => $info)
                                <label class="flex items-center gap-3 p-3.5 rounded-2xl border border-gray-200 cursor-pointer hover:border-primary transition-all">
                                    <input type="radio" name="pace" value="{{ $val }}" {{ ($travelStyle['pace'] ?? 'balanced') === $val ? 'checked' : '' }}
                                           class="text-primary focus:ring-primary">
                                    <x-icon name="{{ $info['icon'] }}" class="w-4 h-4 text-primary shrink-0" />
                                    <span class="text-xs font-semibold text-gray-800">{{ $info['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Favorite Vibe Tags Multi-select --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            {{ __('Travel Interests & Vibes (Multi-select)') }}
                        </label>
                        @php
                            $availableVibes = [
                                'Food & Dining', 'Photography', 'Nature & Trekking', 'Relaxing',
                                'Beach & Islands', 'History & Culture', 'Nightlife', 'Coffee Tour',
                                'Adventure & Sports', 'Shopping'
                            ];
                            $userInterests = $travelStyle['interests'] ?? [];
                        @endphp
                        <div class="flex flex-wrap gap-2.5">
                            @foreach($availableVibes as $vibe)
                                <label class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-2xl text-xs font-medium border cursor-pointer transition-all has-[:checked]:bg-primary has-[:checked]:text-white has-[:checked]:border-primary hover:border-gray-300">
                                    <input type="checkbox" name="interests[]" value="{{ $vibe }}" {{ in_array($vibe, $userInterests) ? 'checked' : '' }} class="hidden">
                                    <span>{{ $vibe }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Locale & Currency --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                {{ __('Language') }}
                            </label>
                            <select name="locale" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="en" {{ ($user->locale ?? 'en') === 'en' ? 'selected' : '' }}>English (Default)</option>
                                <option value="vi" {{ ($user->locale ?? 'en') === 'vi' ? 'selected' : '' }}>Tiếng Việt</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                {{ __('Currency') }}
                            </label>
                            <select name="currency" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="VND" {{ ($user->currency ?? 'VND') === 'VND' ? 'selected' : '' }}>VND (₫)</option>
                                <option value="USD" {{ ($user->currency ?? 'VND') === 'USD' ? 'selected' : '' }}>USD ($)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl text-sm font-semibold text-white bg-primary hover:bg-teal-700 shadow-lg shadow-primary/25 transition-all">
                            <x-icon name="check" class="w-4 h-4 text-white" /> {{ __('Update Preferences') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- SECTION 3: SECURITY & PASSWORD --}}
            <div x-show="activeSection === 'security'" x-cloak class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-8">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <x-icon name="lock" class="w-5 h-5 text-primary" /> {{ __('Change Password') }}
                    </h2>
                    <form method="POST" action="{{ route('password.update') }}" class="space-y-4 max-w-lg">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Current Password') }}</label>
                            <input type="password" name="current_password" required
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @error('current_password')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('New Password') }}</label>
                            <input type="password" name="password" required minlength="8"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            @error('password')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">{{ __('Confirm New Password') }}</label>
                            <input type="password" name="password_confirmation" required
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl text-sm font-semibold text-white bg-primary hover:bg-teal-700 shadow-lg shadow-primary/25 transition-all">
                                <x-icon name="check" class="w-4 h-4 text-white" /> {{ __('Update Password') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="pt-6 border-t border-gray-100">
                    <h3 class="text-base font-bold text-gray-900 mb-2">{{ __('Log out other device sessions') }}</h3>
                    <p class="text-xs text-gray-500 mb-4">{{ __('Enter your password to invalidate all other active sessions across browsers and devices.') }}</p>
                    <form method="POST" action="{{ route('profile.logout-others') }}" class="flex items-center gap-3 max-w-md">
                        @csrf
                        <input type="password" name="password" placeholder="{{ __('Your current password') }}" required
                               class="w-full px-4 py-2 rounded-xl border border-gray-200 text-sm">
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 shrink-0">
                            {{ __('Log Out Others') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- SECTION 4: PRIVACY --}}
            <div x-show="activeSection === 'privacy'" x-cloak class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <x-icon name="shield" class="w-5 h-5 text-primary" /> {{ __('Privacy & Visibility') }}
                </h2>

                <form method="POST" action="{{ route('profile.privacy.update') }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="flex items-start justify-between gap-4 p-4 rounded-2xl bg-gray-50">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">{{ __('Public Profile') }}</h4>
                            <p class="text-xs text-gray-500 mt-0.5">{{ __('Allow others to view your travel stories, badges, and shared trips via your @username URL.') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="hidden" name="is_profile_public" value="0">
                            <input type="checkbox" name="is_profile_public" value="1" {{ $user->is_profile_public ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <div class="flex items-start justify-between gap-4 p-4 rounded-2xl bg-gray-50">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">{{ __('Show Trip Budget Publicly') }}</h4>
                            <p class="text-xs text-gray-500 mt-0.5">{{ __('If disabled, total budget numbers on your public trips will remain hidden from strangers.') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="hidden" name="show_budget_publicly" value="0">
                            <input type="checkbox" name="show_budget_publicly" value="1" {{ $user->show_budget_publicly ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl text-sm font-semibold text-white bg-primary hover:bg-teal-700 shadow-lg shadow-primary/25 transition-all">
                            <x-icon name="check" class="w-4 h-4 text-white" /> {{ __('Save Privacy Settings') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- SECTION 5: NOTIFICATIONS --}}
            <div x-show="activeSection === 'notifications'" x-cloak class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <x-icon name="bell" class="w-5 h-5 text-primary" /> {{ __('Notification Preferences') }}
                </h2>

                <form method="POST" action="{{ route('profile.notifications.update') }}" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="flex items-start justify-between gap-4 p-4 rounded-2xl bg-gray-50">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">{{ __('In-App Notifications') }}</h4>
                            <p class="text-xs text-gray-500 mt-0.5">{{ __('Receive alerts inside the bell drawer when somebody likes your post, comments, or votes.') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="hidden" name="notify_in_app" value="0">
                            <input type="checkbox" name="notify_in_app" value="1" {{ $user->notify_in_app ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <div class="flex items-start justify-between gap-4 p-4 rounded-2xl bg-gray-50">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900">{{ __('Email Notifications') }}</h4>
                            <p class="text-xs text-gray-500 mt-0.5">{{ __('Receive important booking confirmations and trip invitation summaries via email.') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="hidden" name="notify_email" value="0">
                            <input type="checkbox" name="notify_email" value="1" {{ $user->notify_email ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-2xl text-sm font-semibold text-white bg-primary hover:bg-teal-700 shadow-lg shadow-primary/25 transition-all">
                            <x-icon name="check" class="w-4 h-4 text-white" /> {{ __('Save Notifications') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- SECTION 6: DANGER ZONE --}}
            <div x-show="activeSection === 'danger'" x-cloak class="bg-red-50/50 rounded-3xl p-6 sm:p-8 border border-red-100 shadow-sm space-y-6">
                <div>
                    <h2 class="text-xl font-bold text-red-600 flex items-center gap-2">
                        <x-icon name="alert-triangle" class="w-5 h-5 text-red-600" /> {{ __('Delete Account') }}
                    </h2>
                    <p class="text-xs text-gray-600 mt-1 max-w-xl">
                        {{ __('Once you delete your account, all of your created trips, travel memories, comments, and bookings will be permanently wiped. This action cannot be undone.') }}
                    </p>
                </div>

                <div>
                    <button type="button" @click="deleteModal = true"
                            class="px-5 py-2.5 rounded-2xl text-sm font-semibold text-white bg-red-600 hover:bg-red-700 shadow-md shadow-red-600/20 transition-all">
                        {{ __('Delete My Account Permanently') }}
                    </button>
                </div>

                {{-- Delete Confirmation Modal --}}
                <div x-show="deleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-gray-100" @click.outside="deleteModal = false">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('Are you absolutely sure?') }}</h3>
                        <p class="text-xs text-gray-500 mb-6 leading-relaxed">
                            {{ __('Please confirm your password to permanently delete your account and all associated travel records.') }}
                        </p>

                        <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
                            @csrf
                            @method('DELETE')

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">{{ __('Enter Password') }}</label>
                                <input type="password" name="password" required
                                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-red-200 focus:border-red-500">
                                @error('password')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3">
                                <button type="button" @click="deleteModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-red-600 hover:bg-red-700">
                                    {{ __('Confirm Delete') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

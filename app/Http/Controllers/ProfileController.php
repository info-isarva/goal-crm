<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
         $countries = [
            'India', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Singapore', 'Japan', 'China', 'Other'
        ];
        return view('profile.edit', [
            'user' => $request->user(),
            'countries' => $countries,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        // Ensure validation messages are passed to the view and displayed under each field
        $messages = [
            'name.required' => 'Please enter your name.',
            'name.regex' => 'The name must start with a letter.',
            'name.max' => 'The name may not exceed 255 characters.',
            'mobile.max' => 'The phone number may not exceed 20 characters.',
            'mobile.regex' => 'Please enter a valid mobile number (with country code if applicable).',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'The email address may not exceed 255 characters.',
            'email.unique' => 'This email address is already in use.',
            'address.max' => 'The address may not exceed 255 characters.',
            'city.regex' => 'The city name contains invalid characters.',
            'state.regex' => 'The state name contains invalid characters.',
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.max' => 'The avatar size may not exceed 2MB.',
            'avatar.mimes' => 'The avatar must be a file of type: jpg, jpeg, png, gif.',
        ];

        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'regex:/^[A-Za-z].*/',
                    'max:255'
                ],
                'mobile' => [
                    'nullable',
                    'string',
                    'max:20',
                    'regex:/^\+?[0-9\-\s]{10,20}$/',
                ],
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    'regex:/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/',
                    Rule::unique(User::class)->ignore($user->getKey()),
                ],
                'address' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z .\'-]+$/'],
                'state' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z .\'-]+$/'],
                'country' => ['nullable', 'string', 'max:100'],
                'avatar' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,gif'],
            ], $messages);

        // Handle avatar upload
    if ($request->hasFile('avatar')) {
        // Delete old avatar if exists
        if ($user->avatar) {
            $oldPath = public_path('assets/employee_profile_image/' . $user->avatar);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Store new avatar into public/assets/employee_profile_image/avatars/{user_id}
        $file = $request->file('avatar');
        $extension = \App\Support\SafeUpload::imageExtension($file);
        if ($extension === null) {
            return back()->withErrors(['avatar' => $messages['avatar.mimes']])->withInput();
        }
        $folder = 'avatars/' . $user->id;
        $filename = \Illuminate\Support\Str::random(32) . '.' . $extension;
        $relativeDir = 'assets/employee_profile_image/' . $folder;

        $destination = public_path($relativeDir);
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);
        $path = $folder . '/' . $filename; // store path relative to assets/employee_profile_image
        \Log::info('ProfileController: avatar stored', ['user_id' => $user->id, 'path' => $path]);
        $user->avatar = $path;
    }

    // Update user basic info
    $user->fill([
        'name' => $validated['name'],
        'email' => $validated['email'],
    ]);

    // Reset email verification if email changed
    if ($user->isDirty('email')) {
        $user->email_verified_at = null;
    }

    $user->save();

    // Update or create user_details
    $user->userDetail()->updateOrCreate(
        ['user_id' => $user->id],
        [
            'address' => $validated['address'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? null,
            'updated_by' => $user->id,
        ]
    );

    // Use a status token that the views expect (e.g. 'profile-updated')
    return Redirect::route('profile.edit')->with('success', 'Profile Updated successfully!');
    }
    // Add userDetail relationship to User model if not present

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update user's email 2FA setting.
     */
    public function update2FA(Request $request): RedirectResponse
    {
        $user = $request->user();
        $enabled = $request->has('2fa_enabled');
    $user->{"2fa_enabled"} = $enabled;
        $user->save();
        // If the logged-in user has just enabled 2FA for their own account, mark
        // this session as having passed 2FA so we don't immediately redirect
        // them and hide menus/contents. Future logins will still require 2FA.
        if ($request->user() && $request->user()->id === $user->id) {
            if ($enabled) {
                $request->session()->put('2fa:passed', true);
            } else {
                $request->session()->forget('2fa:passed');
            }
        }

        return Redirect::route('profile.edit')->with('status_2fa', $enabled ? 'Email 2FA enabled.' : 'Email 2FA disabled.');
    }
}

<?php

declare(strict_types=1);

return [
    'sections' => [
        'personal' => 'Personal',
        'professional' => 'Professional',
        'about' => 'About',
        'address' => 'Location',
        'social_links' => 'Social Links',
        'availability' => 'Availability',
        'preferences' => 'Preferences',
        'connections' => 'Connections',
    ],

    'fields' => [
        'nickname' => 'Nickname',
        'birthdate' => 'Birthdate',
        'headline' => 'Headline',
        'seniority_level' => 'Seniority Level',
        'years_experience' => 'Years of Experience',
        'about' => 'About',
        'platform' => 'Platform',
        'handle' => 'Handle / URL',
        'country' => 'Country (ISO)',
        'state' => 'State (UF)',
        'city' => 'City',
        'avatar' => 'Photo',
        'cover' => 'Cover',
        'available_for_proposals' => 'Available for proposals',
        'start_availability' => 'Start availability',
        'is_open_to_remote' => 'Open to remote work',
        'willing_to_relocate' => 'Willing to relocate',
        'has_disability' => 'Person with a disability',
        'employment_types' => 'Employment type',
    ],

    'placeholders' => [
        'nickname' => 'How do you like to be called?',
        'headline' => 'Your job title or role',
        'about' => 'Tell us about yourself...',
        'handle' => '@username or https://...',
    ],

    'hints' => [
        'headline' => 'e.g. Frontend Developer, Product Designer',
        'available_for_proposals' => 'When active, recruiters will see a green badge on your profile',
        'has_disability' => 'Sensitive information — used only for affirmative-action roles.',
    ],

    'actions' => [
        'save' => 'Save profile',
        'add_social_link' => 'Add social link',
    ],

    'notifications' => [
        'saved' => 'Profile saved successfully!',
        'no_profile' => 'Profile not found for this tenant.',
    ],
];

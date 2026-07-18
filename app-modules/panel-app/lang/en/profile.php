<?php

declare(strict_types=1);

return [
    'sections' => [
        'personal' => 'Personal',
        'professional' => 'Professional',
        'about' => 'About',
        'address' => 'Location',
        'skills' => 'Skills',
        'social_links' => 'Social Links',
        'availability' => 'Availability',
        'preferences' => 'Preferences',
        'connections' => 'Connections',
        'work_experiences' => 'Work experience',
    ],

    'fields' => [
        'nickname' => 'Nickname',
        'birthdate' => 'Birthdate',
        'headline' => 'Headline',
        'seniority_level' => 'Seniority Level',
        'years_experience' => 'Years of Experience',
        'about' => 'About',
        'skill' => 'Skill',
        'proficiency' => 'Level',
        'skill_years_experience' => 'Years',
        'platform' => 'Platform',
        'handle' => 'Handle / URL',
        'country' => 'Country',
        'state' => 'State',
        'city' => 'City',
        'avatar' => 'Photo',
        'cover' => 'Cover',
        'available_for_proposals' => 'Available for proposals',
        'start_availability' => 'Start availability',
        'company_name' => 'Company',
        'position' => 'Position',
        'experience_description' => 'Description',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'is_currently_working_here' => 'I currently work here',
        'expected_salary_min' => 'Expected salary (min)',
        'expected_salary_max' => 'Expected salary (max)',
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
        'city_search' => 'Search city...',
    ],

    'hints' => [
        'headline' => 'e.g. Frontend Developer, Product Designer',
        'available_for_proposals' => 'When active, recruiters will see a green badge on your profile',
        'city' => 'If your city is not listed, search for it.',
        'has_disability' => 'Sensitive information — used only for affirmative-action roles.',
        'expected_salary' => 'Monthly amount in BRL. Private, used only in proposals.',
        'skills' => 'Pick your skills and set your level and years of experience for each.',
    ],

    'actions' => [
        'save' => 'Save profile',
        'add_social_link' => 'Add social link',
        'add_work_experience' => 'Add experience',
        'add_skill' => 'Add skill',
        'change_avatar' => 'Change photo',
        'change_cover' => 'Change cover',
        'save_avatar' => 'Save photo',
        'save_cover' => 'Save cover',
    ],

    'notifications' => [
        'saved' => 'Profile saved successfully!',
        'avatar_updated' => 'Photo updated successfully!',
        'cover_updated' => 'Cover updated successfully!',
        'no_profile' => 'Profile not found for this tenant.',
    ],

    'page' => [
        'subtitle' => 'Fill in the fields and watch your card being built in real time',
    ],
];

<?php

declare(strict_types=1);

namespace He4rt\Profile\Http\Resources;

use He4rt\Profile\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Profile
 */
final class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nickname' => $this->nickname,
            'headline' => $this->headline,
            'about' => $this->about,
            'seniority_level' => $this->seniority_level?->value,
            'years_experience' => $this->years_experience,
            'social_links' => $this->social_links ?? [],
            'available_for_proposals' => $this->available_for_proposals,
            'start_availability' => $this->start_availability?->value,
            'expected_salary_min' => $this->expected_salary_min,
            'expected_salary_max' => $this->expected_salary_max,
            'preferences' => $this->preferences->toArray(),
            'skills' => $this->profileSkills->map(static fn ($profileSkill): array => [
                'id' => $profileSkill->skill->id,
                'name' => $profileSkill->skill->name,
                'category' => $profileSkill->skill->category->value,
                'proficiency' => $profileSkill->proficiency->value,
                'years_experience' => $profileSkill->years_experience,
            ])->all(),
            'work_experiences' => $this->workExperiences->map(static fn ($experience): array => [
                'id' => $experience->id,
                'company_name' => $experience->company_name,
                'position' => $experience->position,
                'description' => $experience->description,
                'start_date' => $experience->start_date->toDateString(),
                'end_date' => $experience->end_date?->toDateString(),
                'is_currently_working_here' => $experience->is_currently_working_here,
            ])->all(),
        ];
    }
}

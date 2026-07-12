<?php

declare(strict_types=1);

namespace He4rt\Profile\Actions;

use He4rt\Profile\DTOs\ProfileSkillDTO;
use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\Skill;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SyncProfileSkills
{
    /**
     * Reconcilia o conjunto de skills de um profile com a lista informada:
     * remove as ausentes, cria as novas e atualiza nível/anos das existentes.
     *
     * @param  list<ProfileSkillDTO>  $skills
     */
    public function handle(Profile $profile, array $skills): Profile
    {
        $this->validate($skills);

        DB::transaction(static function () use ($profile, $skills): void {
            $keepSkillIds = array_map(
                static fn (ProfileSkillDTO $skill): string => $skill->skillId,
                $skills,
            );

            $profile->profileSkills()
                ->when($keepSkillIds !== [], fn ($query) => $query->whereNotIn('skill_id', $keepSkillIds))
                ->delete();

            foreach ($skills as $skill) {
                $profile->profileSkills()->updateOrCreate(
                    ['skill_id' => $skill->skillId],
                    [
                        'proficiency' => $skill->proficiency,
                        'years_experience' => $skill->yearsExperience,
                    ],
                );
            }
        });

        return $profile->refresh();
    }

    /**
     * @param  list<ProfileSkillDTO>  $skills
     */
    private function validate(array $skills): void
    {
        $errors = [];

        $skillIds = array_map(static fn (ProfileSkillDTO $skill): string => $skill->skillId, $skills);

        if (count($skillIds) !== count(array_unique($skillIds))) {
            $errors['skills'] = [__('profile::validation.skills.duplicate')];
        }

        foreach ($skills as $index => $skill) {
            if ($skill->yearsExperience !== null && ($skill->yearsExperience < 0 || $skill->yearsExperience > 50)) {
                $errors[sprintf('skills.%d.years_experience', $index)] = [
                    __('validation.between.numeric', ['attribute' => 'years_experience', 'min' => 0, 'max' => 50]),
                ];
            }
        }

        if ($skillIds !== []) {
            $validIds = Skill::query()
                ->whereIn('id', $skillIds)
                ->pluck('id')
                ->all();

            $invalidIds = array_diff($skillIds, $validIds);

            if ($invalidIds !== []) {
                $errors['skills'] = array_merge(
                    $errors['skills'] ?? [],
                    [__('profile::validation.skills.invalid')],
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}

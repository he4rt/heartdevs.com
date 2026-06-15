<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\SlashCommands;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Throwable;

class AddressCommand extends AbstractSlashCommand
{
    private const array STATES = [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins',
    ];

    protected $name = 'endereco';

    protected $description = 'Defina sua localização (estado e cidade).';

    /** @var array<mixed> */
    protected $options = [];

    /** @var array<mixed> */
    protected $permissions = [];

    protected $admin = false;

    protected $hidden = false;

    public function handle(Interaction $interaction): void
    {
        if (!$this->memberProvider?->user) {
            $interaction->respondWithMessage(
                'Você precisa se apresentar primeiro. Use o comando `/apresentar`.',
                true
            );

            return;
        }

        try {
            $state = mb_strtoupper((string) $this->value('estado'));
            $city = $this->value('cidade');

            if (!isset(self::STATES[$state])) {
                $interaction->respondWithMessage('Estado inválido. Use a sigla (ex: SP, RJ).', true);

                return;
            }

            $this->memberProvider->user->address()->updateOrCreate([], [
                'country' => 'BRA',
                'state' => $state,
                'city' => $city,
            ]);

            $stateName = self::STATES[$state];

            $interaction->respondWithMessage(
                sprintf('Localização atualizada para **%s, %s** 🇧🇷', $city, $stateName),
                true
            );
        } catch (Throwable $throwable) {
            $this->logger()->error('Error AddressCommand:', [$throwable->getMessage()]);

            $interaction->respondWithMessage('Erro ao atualizar localização.', true);
        }
    }

    /**
     * @return array<mixed>
     */
    public function options(): array
    {
        return [
            [
                'name' => 'estado',
                'description' => 'Seu estado (UF)',
                'type' => Option::STRING,
                'required' => true,
                'autocomplete' => true,
            ],
            [
                'name' => 'cidade',
                'description' => 'Sua cidade',
                'type' => Option::STRING,
                'required' => true,
                'max_length' => 100,
            ],
        ];
    }

    /**
     * @return array<string, callable>
     */
    public function autocomplete(): array
    {
        return [
            'estado' => static function (Interaction $interaction, ?string $value): array {
                $states = collect(self::STATES)
                    ->map(fn (string $name, string $uf): string => sprintf('%s - %s', $uf, $name));

                if ($value) {
                    $search = mb_strtolower($value);
                    $states = $states->filter(
                        fn (string $label, string $uf): bool => str_contains(mb_strtolower($label), $search)
                            || str_contains(mb_strtolower($uf), $search)
                    );
                }

                return $states->take(25)->all();
            },
        ];
    }
}

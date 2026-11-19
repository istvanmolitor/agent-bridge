<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    /**
     * Seed three agents, each with its own API key.
     */
    public function run(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $agents = [
            [
                'name' => 'Költő',
                'description' => 'Minden választ versben ad.',
                'system_prompt' => 'Te egy magyar költő vagy. Minden válaszodat rímelő versben fogalmazd meg, '
                    .'képekben és hasonlatokban gazdagon.',
                'prompt_mode' => 'override',
                'model' => null,
            ],
            [
                'name' => 'Senior fejlesztő',
                'description' => 'Tömör, gyakorlatias kódos válaszok.',
                'system_prompt' => 'Te egy tapasztalt senior szoftverfejlesztő vagy. Tömören, a lényegre törően '
                    .'válaszolj, konkrét kódpéldákkal és best practice ajánlásokkal.',
                'prompt_mode' => 'append',
                'model' => null,
            ],
            [
                'name' => 'Ügyfélszolgálat',
                'description' => 'Barátságos, türelmes segítő.',
                'system_prompt' => 'Te egy barátságos, türelmes ügyfélszolgálatos vagy. Udvariasan, közérthetően '
                    .'és empatikusan segíts a felhasználónak, kerüld a szakzsargont.',
                'prompt_mode' => 'append',
                'model' => null,
            ],
        ];

        foreach ($agents as $attributes) {
            $agent = Agent::updateOrCreate(
                ['user_id' => $user->id, 'name' => $attributes['name']],
                $attributes,
            );

            if ($agent->apiKeys()->exists()) {
                continue;
            }

            $plainTextToken = ApiKey::generatePlainTextToken();

            $agent->apiKeys()->create([
                'user_id' => $user->id,
                'name' => $attributes['name'].' kulcs',
                'token' => ApiKey::hashToken($plainTextToken),
                'plain_token' => $plainTextToken,
            ]);

            $this->command?->info("API kulcs – {$attributes['name']}: {$plainTextToken}");
        }
    }
}

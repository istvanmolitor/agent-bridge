<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\ApiKey;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AgentSeeder extends Seeder
{
    /**
     * Seed three agents, each with its own API key.
     */
    public function run(): void
    {
        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $workspace = Workspace::where('user_id', $user->id)
            ->where('name', 'Alapértelmezett')
            ->firstOrFail();

        $agents = [
            [
                'name' => 'Költő',
                'description' => 'Minden választ versben ad.',
                'system_prompt' => 'Te egy magyar költő vagy. Minden válaszodat rímelő versben fogalmazd meg, '
                    .'képekben és hasonlatokban gazdagon.',
                'prompt_mode' => 'override',
                'model' => null,
                'avatar' => 'kolto.jpg',
            ],
            [
                'name' => 'Senior fejlesztő',
                'description' => 'Tömör, gyakorlatias kódos válaszok.',
                'system_prompt' => 'Te egy tapasztalt senior szoftverfejlesztő vagy. Tömören, a lényegre törően '
                    .'válaszolj, konkrét kódpéldákkal és best practice ajánlásokkal.',
                'prompt_mode' => 'append',
                'model' => null,
                'avatar' => 'senior-fejleszto.jpg',
            ],
            [
                'name' => 'Ügyfélszolgálat',
                'description' => 'Barátságos, türelmes segítő.',
                'system_prompt' => 'Te egy barátságos, türelmes ügyfélszolgálatos vagy. Udvariasan, közérthetően '
                    .'és empatikusan segíts a felhasználónak, kerüld a szakzsargont.',
                'prompt_mode' => 'append',
                'model' => null,
                'avatar' => 'ugyfelszolgalat.jpg',
            ],
        ];

        foreach ($agents as $attributes) {
            $avatarFile = $attributes['avatar'];
            unset($attributes['avatar']);

            $attributes['avatar_path'] = $this->seedAvatar($avatarFile);

            $agent = Agent::updateOrCreate(
                ['user_id' => $user->id, 'name' => $attributes['name']],
                $attributes,
            );

            if ($agent->sessions()->whereHas('apiKey')->exists()) {
                continue;
            }

            $session = $agent->sessions()->create([
                'user_id' => $user->id,
                'workspace_id' => $workspace->id,
            ]);

            $token = ApiKey::generateToken();

            $user->apiKeys()->create([
                'agent_session_id' => $session->id,
                'name' => $attributes['name'].' kulcs',
                'token' => $token,
            ]);

            $this->command?->info("API kulcs – {$attributes['name']}: {$token}");
        }
    }

    /**
     * Copy a bundled avatar onto the public disk and return its stored path.
     */
    private function seedAvatar(string $file): string
    {
        $path = 'agent-avatars/'.$file;

        Storage::disk('public')->put(
            $path,
            (string) file_get_contents(__DIR__.'/avatars/'.$file),
        );

        return $path;
    }
}

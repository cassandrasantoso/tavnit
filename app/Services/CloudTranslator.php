<?php

namespace App\Services;

use Google\Auth\ApplicationDefaultCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Translates text with Google Cloud Translation (Advanced, v3).
 *
 * Japanese to English also runs through our glossary, so words like
 * 給水スポット always come out as "refill spot".
 */
class CloudTranslator
{
    // Google only hosts glossaries in this region, so we call it here too.
    private const REGION = 'us-central1';

    private const SUPPORTED_LANGUAGES = ['ja', 'en'];

    /**
     * Returns both versions so we can compare them:
     *   'plain'    => Google's translation without the glossary
     *   'glossary' => the same translation with our terms applied (null if not used)
     */
    public function translate(string $text, string $from = 'ja', string $to = 'en'): array
    {
        return $this->translateMany([$text], $from, $to)[0];
    }

    /**
     * Translates several texts in one request. Returns one result per text,
     * in the same order, each shaped like translate().
     *
     * @param  list<string>  $texts
     * @return list<array{plain: string, glossary: ?string}>
     */
    public function translateMany(array $texts, string $from = 'ja', string $to = 'en', int $timeoutSeconds = 20): array
    {
        $this->checkLanguages($from, $to);

        if ($texts === []) {
            return [];
        }

        $project = config('services.google_translate.project');

        $request = [
            'sourceLanguageCode' => $from,
            'targetLanguageCode' => $to,
            'contents' => array_values($texts),
            'mimeType' => 'text/plain',
        ];

        // Our glossary only goes one way: Japanese to English.
        $glossaryId = config('services.google_translate.glossary');
        if ($glossaryId && $from === 'ja' && $to === 'en') {
            $request['glossaryConfig'] = [
                'glossary' => "projects/{$project}/locations/".self::REGION."/glossaries/{$glossaryId}",
            ];
        }

        $url = "https://translation.googleapis.com/v3/projects/{$project}/locations/".self::REGION.':translateText';

        $response = Http::timeout($timeoutSeconds)
            ->withToken($this->getAccessToken())
            ->withHeaders(['x-goog-user-project' => $project])
            ->post($url, $request);

        $response->throw(); // stop here if Google returned an error

        $result = $response->json();

        $results = [];
        foreach (array_keys($request['contents']) as $i) {
            $results[] = [
                'plain' => $result['translations'][$i]['translatedText'] ?? '',
                'glossary' => $result['glossaryTranslations'][$i]['translatedText'] ?? null,
            ];
        }

        return $results;
    }

    private function checkLanguages(string $from, string $to): void
    {
        $bothSupported = in_array($from, self::SUPPORTED_LANGUAGES, true)
            && in_array($to, self::SUPPORTED_LANGUAGES, true);

        if (! $bothSupported || $from === $to) {
            throw new InvalidArgumentException("Can't translate from '{$from}' to '{$to}'.");
        }
    }

    private function getAccessToken(): string
    {
        // A token lasts about an hour, so we keep it for 45 minutes
        // instead of asking Google for a new one on every click.
        return Cache::remember('google_access_token', now()->addMinutes(45), function () {
            $credentials = ApplicationDefaultCredentials::getCredentials(
                'https://www.googleapis.com/auth/cloud-platform'
            );
            $token = $credentials->fetchAuthToken();

            if (empty($token['access_token'])) {
                throw new RuntimeException('Could not get a Google access token. Did you run gcloud auth application-default login?');
            }

            return $token['access_token'];
        });
    }
}

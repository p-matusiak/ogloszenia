<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

/**
 * Blokuje nowe bezpośrednie zapytania Eloquent poza warstwą repozytoriów.
 * Istniejące naruszenia są wpisane na białą listę i muszą być stopniowo migrowane.
 */
final class RepositoryOnlyDatabaseAccessTest extends TestCase
{
    private const array ALLOWLIST = [
        'app/Http/Controllers/AdPageController.php',
        'app/Http/Controllers/EmailVerificationController.php',
        'app/Http/Controllers/CategoryPageController.php',
        'app/Http/Controllers/Api/V1/MyAdsController.php',
        'app/Http/Controllers/Api/V1/Admin/CategoriesController.php',
        'app/Http/Controllers/Seo/AdFeedController.php',
        'app/Actions/Categories/DeleteCategoryAction.php',
        'app/Services/Seo/SitemapUrlProvider.php',
        'app/Services/CategoryClosureRepository.php',
        'app/Support/AdSlugGenerator.php',
        'app/Support/SellerSlugGenerator.php',
        'app/Support/CategorySlugGenerator.php',
        'app/Search/Database/DatabaseAdSearchEngine.php',
    ];

    private const array FORBIDDEN_PATTERNS = [
        '/\bAd::query\s*\(/',
        '/\bUser::query\s*\(/',
        '/\bCategory::query\s*\(/',
        '/\bConversation::query\s*\(/',
        '/\bMessage::query\s*\(/',
        '/\bDB::table\s*\(/',
    ];

    #[Test]
    public function application_layers_outside_repositories_do_not_query_models_directly(): void
    {
        $violations = [];

        foreach ($this->scannedPhpFiles() as $file) {
            $relativePath = $this->relativePath($file);

            if ($this->isAllowlisted($relativePath) || $this->isRepositoryLayer($relativePath)) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            assert(is_string($contents));

            foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                if (preg_match($pattern, $contents) === 1) {
                    $violations[] = $relativePath.' matches '.$pattern;
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Direct database access must live in repositories.\n".implode("\n", $violations),
        );
    }

    /**
     * @return list<SplFileInfo>
     */
    private function scannedPhpFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('app')),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file;
        }

        return $files;
    }

    private function relativePath(SplFileInfo $file): string
    {
        return str_replace('\\', '/', substr($file->getPathname(), strlen(base_path().DIRECTORY_SEPARATOR)));
    }

    private function isAllowlisted(string $relativePath): bool
    {
        return in_array($relativePath, self::ALLOWLIST, true);
    }

    private function isRepositoryLayer(string $relativePath): bool
    {
        return str_starts_with($relativePath, 'app/Repositories/Eloquent/')
            || str_starts_with($relativePath, 'app/Models/')
            || str_starts_with($relativePath, 'app/Providers/')
            || str_starts_with($relativePath, 'app/Console/');
    }
}

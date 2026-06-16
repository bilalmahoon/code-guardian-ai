<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Repository\Models\Repository;
use App\Infrastructure\RepositoryProviders\RepositoryProviderFactory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $repositoryId = $request->route('repositoryId');
        $provider     = $request->route('provider');

        $repository = Repository::find($repositoryId);

        if (! $repository) {
            return response()->json(['message' => 'Repository not found'], 404);
        }

        $secret    = $repository->webhook_secret ? decrypt($repository->webhook_secret) : null;
        $signature = $request->header('X-Hub-Signature-256')
            ?? $request->header('X-Hub-Signature')
            ?? '';

        if ($secret && $signature) {
            try {
                $adapter = RepositoryProviderFactory::make($provider);
                $valid   = $adapter->verifyWebhookSignature(
                    $request->getContent(),
                    $signature,
                    $secret
                );

                if (! $valid) {
                    return response()->json(['message' => 'Invalid webhook signature'], 401);
                }
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Webhook verification failed'], 401);
            }
        }

        return $next($request);
    }
}

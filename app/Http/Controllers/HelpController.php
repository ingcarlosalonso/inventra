<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HelpController extends Controller
{
    private const TOPICS = [
        'dashboard',
        'products',
        'composite-products',
        'promotions',
        'product-types',
        'presentations',
        'suppliers',
        'receptions',
        'clients',
        'sales',
        'quotes',
        'orders',
        'daily-cashes',
        'reports',
        'configuration',
    ];

    private const TOPIC_GROUPS = [
        'main' => ['dashboard'],
        'inventory' => ['products', 'composite-products', 'promotions', 'product-types', 'presentations', 'suppliers', 'receptions'],
        'commercial' => ['clients', 'sales', 'quotes', 'orders', 'daily-cashes', 'reports'],
        'configuration' => ['configuration'],
    ];

    public function show(string $topic = 'dashboard'): Response
    {
        if (! in_array($topic, self::TOPICS, true)) {
            throw new NotFoundHttpException;
        }

        $locale = app()->getLocale();
        $path = resource_path("help/{$locale}/{$topic}.md");

        if (! File::exists($path)) {
            $path = resource_path("help/es/{$topic}.md");
        }

        if (! File::exists($path)) {
            throw new NotFoundHttpException;
        }

        $markdown = File::get($path);

        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $content = $converter->convert($markdown)->getContent();

        return Inertia::render('Help/Index', [
            'topic' => $topic,
            'content' => $content,
            'topics' => self::TOPICS,
            'topicGroups' => self::TOPIC_GROUPS,
        ]);
    }
}

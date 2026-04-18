<?php

namespace App\Core\Themes;

use App\Core\Contracts\Extensions\Themes\ThemeSlotRegistry;
use App\Core\Extensions\Hooks\ThemeSlotBlock;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Throwable;

class ThemeSlotRenderer
{
    /**
     * @var array<int, string>
     */
    protected array $supportedSlots = [
        'hero',
        'sidebar',
        'footer_cta',
    ];

    public function __construct(
        protected ThemeSlotRegistry $registry,
        protected ThemeViewResolver $views,
        protected ViewFactory $viewFactory,
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function render(string $slot, array $context = []): string
    {
        if (! $this->supports($slot)) {
            return '';
        }

        $blocks = $this->renderedBlocks($slot, $context);

        if ($blocks === []) {
            return '';
        }

        $view = $this->views->themeViewExists('slots.'.$slot)
            ? 'theme::slots.'.$slot
            : 'front.slots.default';

        return $this->viewFactory->make($view, [
            'slot' => $slot,
            'blocks' => $blocks,
            'context' => $context,
        ])->render();
    }

    public function supports(string $slot): bool
    {
        return in_array($slot, $this->supportedSlots, true);
    }

    /**
     * @return array<int, string>
     */
    public function supportedSlots(): array
    {
        return $this->supportedSlots;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array{key: string, plugin_slug: string, slot: string, priority: int, html: string}>
     */
    protected function renderedBlocks(string $slot, array $context): array
    {
        $blocks = $this->registry->themeSlotBlocks($slot);

        usort($blocks, function (ThemeSlotBlock $left, ThemeSlotBlock $right): int {
            $priorityComparison = $left->priority() <=> $right->priority();

            if ($priorityComparison !== 0) {
                return $priorityComparison;
            }

            return strcmp($left->uniqueKey(), $right->uniqueKey());
        });

        $rendered = [];

        foreach ($blocks as $block) {
            $html = $this->renderBlock($block, $slot, $context);

            if ($html === null) {
                continue;
            }

            $rendered[] = [
                'key' => $block->key(),
                'plugin_slug' => $block->pluginSlug(),
                'slot' => $block->slot(),
                'priority' => $block->priority(),
                'html' => $html,
            ];
        }

        return $rendered;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function renderBlock(ThemeSlotBlock $block, string $slot, array $context): ?string
    {
        $view = $block->themeView() !== null
            ? $this->views->resolve($block->themeView(), $block->view())
            : $block->view();

        if (! $this->viewFactory->exists($view)) {
            return null;
        }

        try {
            $data = $this->resolveBlockData($block, $context);

            if ($data === null) {
                return null;
            }

            return $this->viewFactory->make($view, array_merge($context, $data, [
                'slot' => $slot,
                'block' => $block,
            ]))->render();
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    protected function resolveBlockData(ThemeSlotBlock $block, array $context): ?array
    {
        $data = $block->data();
        $resolver = $block->dataResolver();

        if ($resolver === null) {
            return $data;
        }

        $resolved = $resolver($context);

        if ($resolved === null) {
            return null;
        }

        return array_merge($data, $resolved);
    }
}

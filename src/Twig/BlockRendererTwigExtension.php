<?php

namespace App\Twig;

use App\Service\Render\BlockRenderer;
use App\Entity\Site;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension to render block trees from PageEditor.
 */
final class BlockRendererTwigExtension extends AbstractExtension
{
  public function __construct(private BlockRenderer $blockRenderer) {}
  
  public function getFilters(): array
  {
    return [
      new TwigFilter('render_block', [$this, 'renderBlock'], ['is_safe' => ['html']]),
    ];
  }
  
  /**
   * Render a single block with site context.
   * 
   * @param array $block Block data from page.data.blocks
   * @param Site $site Current site context
   * @return string Rendered HTML
   */
  public function renderBlock(array $block, Site $site): string
  {
    return $this->blockRenderer->render($block, $site);
  }
}

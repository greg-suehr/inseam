<?php
namespace App\Import\Planning;

use App\Import\DTO\Planning\StylePlan;

final class StyleTokenizationEngine
{
  public function tokenize(array $stylesheets): StylePlan
  {
    $allCss = '';
    $tokensUsed = [];
    
    foreach ($stylesheets as $stylesheet) {
      $allCss .= $stylesheet->cssText . "\n";
      preg_match_all('/\.([a-zA-Z][\w-]*)/', $stylesheet->cssText, $matches);
      $tokensUsed = array_merge($tokensUsed, $matches[1]);
    }
    
    $scopeClass = 'compat-' . substr(hash('sha256', $allCss), 0, 8);
    $scopedCss = $this->scopeCss($allCss, $scopeClass);
    
    return new StylePlan(
      tokensUsed: array_unique($tokensUsed),
      unmappedDeclarations: [],
      scopedCompatibilityCss: $scopedCss,
      compatCssScopes: [$scopeClass]
    );
  }

  private function scopeCss(string $css, string $scope): string
  {
    return preg_replace('/([^{}]+){/', ".$scope $1 {", $css);
  }
}

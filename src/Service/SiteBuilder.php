<?php

namespace App\Service;

use App\Entity\Admin;
use App\Entity\Site;
use App\Entity\Page;
use App\Entity\Block;
use App\Entity\Asset;
use Doctrine\ORM\EntityManagerInterface;

class SiteBuilder
{
  public function __construct(private EntityManagerInterface $em) {}
  
  public function createFromTemplate(array $templateData, string $siteName, Admin $owner): Site
  {    
    $site = new Site();
    $site->setName($siteName);
    $site->setDomain($siteName);
    $site->setOwner($owner);

    foreach ($templateData['pages'] as $pageData) {
      $page = new Page();
      $page->setSlug($pageData['slug']);
      $page->setTitle($pageData['title']);
      $page->setSite($site);
      $page->setIsPublished(true);
      
      $this->createBlocks($pageData['blocktree'], $page);
      $this->em->persist($page);
    }
    
    if (isset($templateData['assets'])) {
      foreach ($templateData['assets'] as $assetData) {
        $asset = new Asset();
        $asset->setPath($assetData['path']);
        $asset->setSite($site);
        $this->em->persist($asset);
      }
    }
    
    $this->em->persist($site);
    $this->em->flush();
    
    return $site;
    }
  
  private function createBlocks(array $blockDataList, Page $page, ?Block $parent = null): void
  {
        foreach ($blockDataList as $blockData) {
          $block = new Block();
          $block->setType($blockData['type']);
          $block->setText($blockData['text'] ?? null);
          $block->setPage($page);
          if ($parent) {
            $block->setParent($parent);
          }
          
          $this->em->persist($block);
          
          if (!empty($blockData['children'])) {
            $this->createBlocks($blockData['children'], $page, $block);
          }
        }
    }
}

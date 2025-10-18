<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Block;
use App\Repository\PageRepository;
use App\Repository\AssetRepository;
use App\Service\SiteContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageEditorController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SiteContext $siteContext,
        private PageRepository $pageRepo,
        private AssetRepository $assetRepo
    ) {}


  #[Route('/page/create/new', name: 'page_editor_create', methods: ['GET','POST'])]
  public function create(Request $request): Response
  {
    $site = $this->siteContext->getCurrentSite();
    if (!$site) {
      throw $this->createAccessDeniedException();
    }
    
    $title   = trim((string)($request->request->get('title') ?? $request->query->get('title') ?? 'Untitled Page'));
    $ptype   = trim((string)($request->request->get('page_type') ?? $request->query->get('page_type') ?? 'standard'));
    $btJson  = $request->request->get('blocktree') ?? $request->query->get('blocktree');
    $blocks  = [];
    if ($btJson) {
      try { $parsed = json_decode($btJson, true); if (is_array($parsed)) { $blocks = $parsed; } } catch (\Throwable $e) {}
    }
    
    $page = new Page();
    $page->setTitle($title);
    $page->setPageType($ptype);
    $page->setSlug($this->uniqueSlugForSite($title, $site->getId()));
    $page->setIsPublished(false);
    $page->setData(['blocktree' => $blocks]);
    $now = new \DateTimeImmutable();
    $page->setCreatedAt($now);
    $page->setUpdatedAt($now);
    $page->setSite($site);
    $this->em->persist($page);
    $this->em->flush();

    return $this->redirectToRoute('page_editor_edit', ['id' => $page->getId()]);
  }
  
  #[Route('/page/{id}/edit', name: 'page_editor_edit', methods: ['GET'])]
  public function edit(Page $page): Response
  {
    $site = $this->siteContext->getCurrentSite();

    if (!$site || $page->getSite() !== $site) {
      throw $this->createAccessDeniedException();
    }
    
    $pageData = $this->dereferenceBlocks($page->getData());
    
    return $this->render('page_editor/edit.html.twig', [
      'page' => $page,
      'pageData' => $pageData,
      'site' => $site,
    ]);
  }
  
  #[Route('/page/{id}/save', name: 'page_editor_save', methods: ['POST'])]
  public function save(Page $page, Request $request): JsonResponse
  {
    $site = $this->siteContext->getCurrentSite();
    
    if (!$site || $page->getSite() !== $site) {
      throw $this->createAccessDeniedException();
    }
    
    $data = json_decode($request->getContent(), true);

    if (!isset($data['blocktree'])) {
      return new JsonResponse(['error' => 'Invalid data'], 400);
    }
    
    $pageData = $page->getData();
    $pageData['blocktree'] = $data['blocktree'];
    $pageData['html'] = '';
    $page->setData($pageData);
    
    $this->em->flush();
    
    return new JsonResponse([
      'success' => true,
      'message' => 'Page saved successfully'
    ]);
  }

  #[Route('/page/{id}/block/create', name: 'page_editor_block_create', methods: ['POST'])]
  public function createBlock(Page $page, Request $request): JsonResponse
  {
    $site = $this->siteContext->getCurrentSite();
        
    if (!$site || $page->getSite() !== $site) {
      throw $this->createAccessDeniedException();
    }
    
    $data = json_decode($request->getContent(), true);
    
    $block = new Block();
    $block->setType($data['type'] ?? 'paragraph');
    $block->setText($data['text'] ?? '');
    $block->setData($data['data'] ?? []);
    $block->setPage($page);
    
    $this->em->persist($block);
    $this->em->flush();
    
    return new JsonResponse([
      'success' => true,
      'blockId' => $block->getId(),
      'block' => $this->serializeBlock($block)
        ]);
  }
  
  #[Route('/page/block/{id}/update', name: 'page_editor_block_update', methods: ['POST'])]
  public function updateBlock(Block $block, Request $request): JsonResponse
  {
    $data = json_decode($request->getContent(), true);
    
    if (isset($data['text'])) {
      $block->setText($data['text']);
    }
    
    if (isset($data['data'])) {
      $block->setData(array_merge($block->getData() ?? [], $data['data']));
    }
    
    $this->em->flush();
    
    return new JsonResponse([
      'success' => true,
      'block' => $this->serializeBlock($block)
    ]);
  }
  
  #[Route('/page/assets/browse', name: 'page_editor_assets_browse', methods: ['GET'])]
  public function browseAssets(Request $request): Response
  {
    $site = $this->siteContext->getCurrentSite();
        
    if (!$site) {
      throw $this->createAccessDeniedException();
    }
    
    $assets = $this->assetRepo->findBy(['site' => $site], ['id' => 'DESC']);
    
    if ($request->query->get('modal')) {
      return $this->render('page_editor/modals/asset_picker.html.twig', [
        'assets' => $assets,
      ]);
    }

    return $this->render('page_editor/assets/browse.html.twig', [
      'assets' => $assets,
      'site' => $site,
    ]);
  }

  #[Route('/page/assets/upload', name: 'page_editor_assets_upload', methods: ['POST'])]
  public function uploadAsset(Request $request): JsonResponse
  {
    $site = $this->siteContext->getCurrentSite();
    
    if (!$site) {
      throw $this->createAccessDeniedException();
    }
    
    // TODO: Implement file upload handling
    return new JsonResponse([
      'success' => false,
      'message' => 'Upload not yet implemented'
    ], 501);
  }
  
  /**
   * Dereference block IDs to full block data
   */
  private function dereferenceBlocks(array $pageData): array
  {
    if (!isset($pageData['blocktree'])) {
      return $pageData;
    }
    
    $blocktree = $pageData['blocktree'];
    $dereferenced = [];
    
    foreach ($blocktree as $item) {
      $dereferenced[] = $this->dereferenceSingleBlock($item);
    }
    
    $pageData['blocktree'] = $dereferenced;
    return $pageData;
  }
  
  private function dereferenceSingleBlock(array|int $blockItem): array
  {
    // If it's just an ID, fetch the block
    if (is_int($blockItem)) {
      $block = $this->em->find(Block::class, $blockItem);
      if (!$block) {
        return ['type' => 'error', 'text' => 'Block not found'];
      }
      return $this->serializeBlock($block);
    }

    // If it's already an array, check for children
    if (isset($blockItem['children'])) {
      $blockItem['children'] = array_map(
        fn($child) => $this->dereferenceSingleBlock($child),
        $blockItem['children']
      );
    }
    
    return $blockItem;
  }

  private function serializeBlock(Block $block): array
  {
    return [
      'id' => $block->getId(),
      'type' => $block->getType(),
      'text' => $block->getText(),
      'data' => $block->getData(),
      'children' => array_map(
        fn($child) => $this->serializeBlock($child),
        $block->getChildren()->toArray()
        ),
    ];
  }
  
  private function uniqueSlugForSite(string $title, int $siteId): string
  {
    $slugger = new AsciiSlugger();
    $base = strtolower($slugger->slug($title ?: 'untitled-page')->toString());
    $slug = $base ?: 'untitled-page';
    
    $i = 0;
    while (true) {
      $candidate = $i === 0 ? $slug : $slug.'-'.$i;
      $exists = $this->pageRepo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.slug = :slug')
            ->andWhere('p.site = :site')
            ->setParameter('slug', $candidate)
            ->setParameter('site', $siteId)
            ->getQuery()
            ->getSingleScalarResult();
      if ((int)$exists === 0) {
        return $candidate;
      }
      $i++;
    }
  }
}

<?php
namespace App\Import\Planning;

use App\Import\DTO\DiscoveryResult;
use App\Import\DTO\ContentGraph\PageNode as GPage;
use App\Import\DTO\ContentGraph\AssetNode as GAsset;
use App\Import\DTO\ContentGraph\StylesheetNode as GStyle;
use App\Import\DTO\ContentGraph\ScriptNode as GScript;
use App\Import\DTO\Planning\AssetPlanItem;
use App\Import\DTO\Planning\ImportPlan;
use App\Import\DTO\Planning\PagePlanItem;
use App\Import\DTO\Planning\RedirectMap;
use App\Import\DTO\Planning\RoutePlanItem;
use App\Import\Entity\StoredImportPlan;
use App\Import\Util\PlanHydrator;
use Doctrine\ORM\EntityManagerInterface;

final class PlanRepository
{
    public function __construct(
      private EntityManagerInterface $em,
      private BlockExtractionEngine $blocks,
      private StyleTokenizationEngine $styles,
      private ScriptPolicyEngine $scripts,
    ) {}

    public function get(string $planId): ImportPlan
    {
        $row = $this->em->getRepository(StoredImportPlan::class)
                        ->findOneBy(['planId' => $planId]);
        if (!$row) throw new \RuntimeException("Plan not found: $planId");
        $data = $row->getPlanJson();

        return PlanHydrator::fromArray($data);
    }

  public function createPlanFromDiscovery(DiscoveryResult $graph): ImportPlan
  {
      $planId = 'plan_' . substr(hash('sha256', $graph->graphId . microtime(true)), 0, 16);
      
      $routes  = [];
      $pages   = [];
      $assets  = [];
      $styleNs = [];
      $scriptNs = [];

      foreach ($graph->nodes as $n) {
        if ($n instanceof GPage)   { $this->addPage($n, $routes, $pages); }
        if ($n instanceof GAsset)  { $this->addAsset($n, $assets); }
        if ($n instanceof GStyle)  { $styleNs[]  = $n; }
        if ($n instanceof GScript) { $scriptNs[] = $n; }
      }
      
      $stylePlan  = $this->styles->tokenize($styleNs);
      $scriptPlan = $this->scripts->classify($scriptNs);
      
      $redirects = new RedirectMap([]);
      
      $plan = new ImportPlan(
        planId:   $planId,
        routes:   $routes,
        pages:    $pages,
        assets:   $assets,
        styles:   $stylePlan,
        scripts:  $scriptPlan,
        redirects:$redirects
      );

      $this->save($plan);
      
      return $plan;
  }

  private function addPage(GPage $p, array &$routes, array &$pages): void
  {
      $slug = $this->deriveSlug($p->title ?: $p->sourceUrl);
      $routes[$p->id] = new RoutePlanItem(
        pageId: $p->id,
        oldUrl: $p->sourceUrl,
        slug:   $slug,
        route:  '/pages/{slug}'
      );
      
      $blockTree = $this->blocks->extract($p->rawHtml);
      
      $pages[$p->id] = new PagePlanItem(
        pageId:     $p->id,
        sourceUrl:  $p->sourceUrl,
        blockTree:  $blockTree,
        sourceHash: $p->hash
      );
    }

  private function addAsset(GAsset $a, array &$assets): void
  {
      // TODO: decide on a targetPath; FS storage computes sharded paths
      $assets[$a->id] = new AssetPlanItem(
        assetId:      $a->id,
        sourceUrl:    $a->sourceUrl,
        expectedHash: $a->hash ?? hash('sha256', $a->sourceUrl),
        targetPath:   '/media/' . $a->id // placeholder; final URL comes from AssetStorage
      );
    }
  
  private function deriveSlug(string $s): string
  {
      $s = strtolower(trim($s));
      $s = preg_replace('/[^a-z0-9]+/', '-', $s);
      $s = trim($s, '-');
      return $s ?: 'untitled';
    }
  
  private function save(ImportPlan $plan): void
  {
      $row = $this->em->getRepository(StoredImportPlan::class)
                  ->findOneBy(['planId' => $plan->planId])
       ?? new StoredImportPlan(
         '', # sessionId
         $plan->planId,
         '', # checksum
         [''] # planJson         
       );
      $row->setPlanJson(PlanHydrator::toArray($plan));
      $this->em->persist($row); $this->em->flush();      
  }

  public function load(string $planId): ImportPlan {
      return $this->get($planId);
  }  
}

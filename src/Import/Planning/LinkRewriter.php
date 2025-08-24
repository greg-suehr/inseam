<?php

namespace App\Import\Planning;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class LinkRewriter  
{
  public function __construct(
    private EntityManagerInterface $em,
    private LoggerInterface $logger
  ) {}
  
  public function swapAssetReferences(string $planId, string $assetId, string $finalUrl): void
  {
        try {
          $this->logger->info('Rewriting asset references', [
            'planId' => $planId,
            'assetId' => $assetId,
            'finalUrl' => $finalUrl
          ]);

          // TODO: verify paramater binding or lack of in executeStatement
          $sql = "UPDATE page_content
                    SET content = REPLACE(content, CONCAT('ASSET:', :assetId), :finalUrl)
                    WHERE content LIKE CONCAT('%ASSET:', :assetId, '%')
                    AND plan_id = :planId";

          # TODO: fix "Indeterminate datatype: 7 ERROR:  could not determine data type of parameter $1"
          /*
          $this->em->getConnection()->executeStatement($sql, [
            'assetId' => $assetId,
            'finalUrl' => $finalUrl,
            'planId' => $planId
          ])
          */;

          $this->logger->info('Asset references updated', [
            'assetId' => $assetId,
            'finalUrl' => $finalUrl
          ]);
        } catch (\Exception $e) {
          $this->logger->error('Failed to rewrite asset references', [
            'planId' => $planId,
            'assetId' => $assetId,
            'error' => $e->getMessage()
            ]);
          throw $e;
        }
    }

  public function updatePageLinks(string $planId, array $redirectMap): void
  {
        try {
          foreach ($redirectMap as $oldUrl => $newRoute) {
            $sql = "UPDATE page_content
                        SET content = REPLACE(content, :oldUrl, :newRoute)
                        WHERE content LIKE CONCAT('%', :oldUrl, '%')
                        AND plan_id = :planId";

            $this->em->getConnection()->executeStatement($sql, [
              'oldUrl' => $oldUrl,
              'newRoute' => $newRoute,
              'planId' => $planId
            ]);
          }
          
          $this->logger->info('Page links updated', [
            'planId' => $planId,
            'redirectCount' => count($redirectMap)
            ]);
          
        } catch (\Exception $e) {
          $this->logger->error('Failed to update page links', [
            'planId' => $planId,
            'error' => $e->getMessage()
            ]);
          throw $e;
        }
    }
}

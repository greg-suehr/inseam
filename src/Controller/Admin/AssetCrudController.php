<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Service\SiteContext;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AssetCrudController extends AbstractCrudController
{
  public function __construct(
    private SiteContext $siteContext,
    private EntityManagerInterface $em,
  ) {}
    
  public static function getEntityFqcn(): string
  {
    return Asset::class;
  }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */

  public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
  {
    $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

     $site = $this->siteContext->getCurrentSite();
     if (!$site) {
        $qb->andWhere('1 = 0');
        return $qb;
    }

     $qb->andWhere('entity.site = :site')
       ->setParameter('site', $site);
     
     $qb->join('entity.site', 's')
       ->andWhere('s.owner = :owner')
       ->setParameter('owner', $this->getUser());
     
     return $qb;
  }
    
}

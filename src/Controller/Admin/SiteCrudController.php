<?php

namespace App\Controller\Admin;

use App\Entity\Site;
use App\Service\SiteService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Messenger\MessageBusInterface;

class SiteCrudController extends AbstractCrudController
{
  public function __construct(
    private SiteService $siteService,
    private EntityManagerInterface $em,
    private MessageBusInterface $bus,
  ) {}
  
  public static function getEntityFqcn(): string
  {
    return Site::class;
  }
  
  public function persistEntity(EntityManagerInterface $em, $entity): void
  {
    if (!($entity instanceof Site)) {
      parent::persistEntity($em, $entity);
      return;
    }

    $em->persist($entity);
    $em->flush();
    
    $this->bus->dispatch(new ProvisionSiteSchema($entity->getId()));
  }

  public function configureActions(Actions $actions): Actions
  {
    $view = Action::new('view', 'View')
        ->linkToRoute('ishere_site_view', fn (Site $s) => ['id' => $s->getId()])
        ->setCssClass('btn btn-success');
    
    return $actions
      ->add(Crud::PAGE_INDEX, $view)
      ->add(Crud::PAGE_EDIT,  $view);
  }
  
  public function configureFields(string $pageName): iterable
  {
    return [
      TextField::new('name'),
      TextField::new('domain'),
    ];
  }

  public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
  {
    $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
    $qb->andWhere('entity.owner = :owner')->setParameter('owner', $this->getUser());
    return $qb;
  }

  public function new(AdminContext $context)
  {
    return $this->redirectToRoute('site_create_from_template',  ['template' => "simple_site" ]);
  }
}

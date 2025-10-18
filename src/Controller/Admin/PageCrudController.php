<?php

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Service\SiteContext;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class PageCrudController extends AbstractCrudController
{
  public function __construct(
    private SiteContext $siteContext,
    private EntityManagerInterface $em,
  ) {}
  
  public static function getEntityFqcn(): string
  {
      return Page::class;
  }

  public function configureFields(string $pageName): iterable
  {
    return [
      TextField::new('title'), 
      TextField::new('slug'),
      BooleanField::new('is_published'),
      TextField::new('htmlContent')
            ->setFormType(CKEditorType::class)
            ->onlyOnForms(),
      TextField::new('htmlContent')
            ->renderAsHtml()
            ->onlyOnIndex()
      ];
  }

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

  public function configureActions(Actions $actions): Actions
  {
    $create = Action::new('create_in_builder', 'New Page')
        ->linkToRoute('page_editor_create');
    
    $editInBuilder = Action::new('edit_in_builder', 'Edit')
        ->linkToRoute('page_editor_edit', function (Page $p) {
            return ['id' => $p->getId()];
        });
    
    return $actions
           ->add(Crud::PAGE_INDEX, $create)
           ->add(Crud::PAGE_INDEX, $editInBuilder)
           ->add(Crud::PAGE_DETAIL, $editInBuilder);
  }

  public function new(AdminContext $context)
  {
    return $this->redirectToRoute('page_editor_create');
  }

  public function edit(AdminContext $context)
  {
    $page = $context->getEntity()->getInstance();
    return $this->redirectToRoute('page_editor_edit', ['id' => $page->getId()]);
  }
}

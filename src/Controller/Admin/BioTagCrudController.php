<?php

namespace App\Controller\Admin;

use App\Entity\BioTag;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BioTagCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BioTag::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
          AssociationField::new('profile')
              ->setCrudController(AdminCrudController::class),
          TextField::new('title'),
          TextField::new('text'),
        ];
    }
}

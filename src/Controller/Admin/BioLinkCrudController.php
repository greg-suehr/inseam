<?php

namespace App\Controller\Admin;

use App\Entity\BioLink;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BioLinkCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BioLink::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
          AssociationField::new('profile')
              ->setCrudController(AdminCrudController::class),
          TextField::new('title'),
          TextField::new('hyperlink'),
        ];
    }
}

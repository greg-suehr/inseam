<?php

namespace App\Controller\Admin;

use App\Entity\Blurb;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BlurbCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Blurb::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
          AssociationField::new('profile')
              ->setCrudController(AdminCrudController::class),
          TextField::new('title'),
          TextField::new('text'),
          DateTimeField::new('timestamp'),
        ];
    }
}

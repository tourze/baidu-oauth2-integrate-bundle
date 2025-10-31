<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2Config;

#[AdminCrud(
    routePath: '/baidu-oauth2/config',
    routeName: 'baidu_oauth2_config',
)]
final class BaiduOAuth2ConfigCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BaiduOAuth2Config::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Baidu 配置')
            ->setEntityLabelInPlural('Baidu OAuth2 配置')
            ->setPageTitle('index', 'Baidu OAuth2 配置列表')
            ->setPageTitle('new', '新建配置')
            ->setPageTitle('edit', '编辑配置')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'clientId'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield TextField::new('clientId', 'API Key')->setRequired(true);
        yield TextField::new('clientSecret', 'Secret Key')->setRequired(true);
        yield TextareaField::new('scope', 'Scope')->hideOnIndex();
        yield BooleanField::new('valid', '是否启用')->renderAsSwitch();
        yield DateTimeField::new('createTime', '创建时间')->hideOnForm();
        yield DateTimeField::new('updateTime', '更新时间')->hideOnForm()->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('clientId')
            ->add('valid')
            ->add('createTime')
        ;
    }
}

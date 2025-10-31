<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2State;

#[AdminCrud(
    routePath: '/baidu-oauth2/state',
    routeName: 'baidu_oauth2_state',
)]
final class BaiduOAuth2StateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BaiduOAuth2State::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('OAuth2 状态')
            ->setEntityLabelInPlural('Baidu OAuth2 状态')
            ->setPageTitle('index', 'Baidu OAuth2 状态列表')
            ->setPageTitle('new', '新建状态')
            ->setPageTitle('edit', '编辑状态')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'state', 'sessionId'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield TextField::new('state', 'State 值')->setRequired(true);
        yield DateTimeField::new('expireTime', '过期时间')->setRequired(true);
        yield BooleanField::new('used', '是否已使用')->renderAsSwitch();
        yield TextField::new('sessionId', '会话ID');
        yield AssociationField::new('config', '关联配置')->setRequired(true);
        yield DateTimeField::new('createTime', '创建时间')->hideOnForm();
        yield DateTimeField::new('updateTime', '更新时间')->hideOnForm()->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('state')
            ->add('used')
            ->add('sessionId')
            ->add('config')
            ->add('createTime')
        ;
    }
}

<?php

declare(strict_types=1);

namespace Tourze\BaiduOauth2IntegrateBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Tourze\BaiduOauth2IntegrateBundle\Entity\BaiduOAuth2User;

#[AdminCrud(
    routePath: '/baidu-oauth2/user',
    routeName: 'baidu_oauth2_user',
)]
final class BaiduOAuth2UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BaiduOAuth2User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Baidu 用户')
            ->setEntityLabelInPlural('Baidu OAuth2 用户')
            ->setPageTitle('index', 'Baidu OAuth2 用户列表')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'baiduUid', 'username'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();
        yield TextField::new('baiduUid', 'Baidu用户ID')->setRequired(true);
        yield TextField::new('username', '用户名');
        yield TextField::new('avatar', '头像URL')->hideOnIndex();
        yield AssociationField::new('config', '配置');
        yield TextField::new('accessToken', 'Access Token')->hideOnIndex();
        yield TextField::new('refreshToken', 'Refresh Token')->hideOnIndex();
        yield IntegerField::new('expiresIn', '有效期(秒)');
        yield DateTimeField::new('expireTime', '过期时间');
        yield ArrayField::new('rawData', '原始数据')->hideOnIndex()->hideOnForm();
        yield DateTimeField::new('createTime', '创建时间')->hideOnForm();
        yield DateTimeField::new('updateTime', '更新时间')->hideOnForm()->hideOnIndex();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('baiduUid')
            ->add('username')
            ->add('expireTime')
            ->add('config')
        ;
    }
}

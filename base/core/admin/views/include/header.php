<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta type="keywords" content="...">
    <meta type="description" content="...">
    <title>Document</title>

    <?php $this->getStyles()?>
    <link
        rel="stylesheet"
        href="<?=PATH . ADMIN_TEMPLATE?>css/forprint-admin.css?v=20260724-0649"
    >
<link rel="stylesheet" href="<?=PATH . ADMIN_TEMPLATE?>css/forprint-admin-collections.css?v=20260725-1830">
<link rel="stylesheet" href="<?=PATH . ADMIN_TEMPLATE?>css/forprint-admin-ui.css?v=20260725-2605">
<link rel="stylesheet" href="<?=PATH . ADMIN_TEMPLATE?>css/forprint-admin-ordering.css?v=20260725-2710">
<link rel="stylesheet" href="<?=PATH . ADMIN_TEMPLATE?>css/forprint-admin-gallery.css?v=20260725-3500">
<link rel="stylesheet" href="<?=PATH . ADMIN_TEMPLATE?>css/forprint-admin-goods-form.css?v=20260725-3500">
</head>
<body>
<div class="vg-carcass fp-admin-shell" data-fp-admin-shell>
    <div class="vg-main fp-admin-topbar">
        <div class="vg-one-of-twenty fp-admin-topbar__brand vg-firm-background-color2  vg-center">
            <a href="<?=PATH?>" target="_blank">
                <div class="vg-element vg-full">
                    <span class="vg-text2 vg-firm-color1">Site</span>
                </div>
            </a>
        </div>
        <div class="vg-element vg-ninteen-of-twenty fp-admin-topbar__content vg-firm-background-color4 vg-space-between  vg-box-shadow">
            <div class="vg-element vg-third">
                <button
                    class="vg-element vg-fifth vg-center fp-admin-sidebar-toggle"
                    id="hideButton"
                    type="button"
                    aria-label="Розгорнути адміністративне меню"
                    aria-expanded="true"
                >
                    <span>
                        <img src="<?=PATH.ADMIN_TEMPLATE?>img/menu-button.png" alt="">
                    </span>
                </button>
                <div class="vg-element vg-wrap-size vg-left vg-search  vg-relative" id="searchButton">
                    <div>
                        <img src="<?=PATH.ADMIN_TEMPLATE?>img/search.png" alt="">
                    </div>
                    <form
                        action="<?=$this->adminPath?>show/<?=htmlspecialchars((string)($this->table ?? 'goods'), ENT_QUOTES, 'UTF-8')?>"
                        autocomplete="off"
                        data-fp-admin-search-form
                    >
                        <input type="text" name="search" class="vg-input vg-text" placeholder="Пошук в адмінці" aria-label="Пошук в адмінці">
                        <input type="hidden" name="search_table" value="<?=$this->table?>">
                        <div class="vg-element vg-firm-background-color4 vg-box-shadow search_links search_res">

                        </div>
                    </form>
                </div>
            </div>
            <!--кнопка-->
            <a href="<?=PATH . \core\base\settings\Settings::get('routes')['admin']['alias']?>/createsitemap" class="vg-element vg-box-shadow sitemap-button fp-admin-create-sitemap" data-fp-admin-create-sitemap>
                            <span class="vg-text vg-firm-color1">
                                Create sitemap
                            </span>
            </a>
            <!--/кнопка-->
            <div class="vg-element vg-fifth">
                <div class="vg-element vg-half vg-right">
                    <div class="vg-element vg-text vg-center">
                        <span class="vg-firm-color5"><?=htmlspecialchars((string)($this->userId['name'] ?? 'admin'), ENT_QUOTES, 'UTF-8')?></span>
                    </div>
                </div>
                <a href="<?=PATH . \core\base\settings\Settings::get('routes')['admin']['alias']?>/login/logout/1"
                   class="vg-element vg-half vg-center"
                   data-fp-admin-logout>
                    <div>
                        <img src="<?=PATH .ADMIN_TEMPLATE?>img/out.png" alt="">
                    </div>
                </a>
            </div>
        </div>
    </div>
    <div class="vg-main vg-right vg-relative fp-admin-workspace">
        <nav
            class="vg-wrap vg-firm-background-color1 vg-center vg-block vg-menu fp-admin-sidebar"
            aria-label="Розділи адміністративної панелі"
            data-fp-admin-menu-sortable
        >

          <?php if($this->menu):?>
                <?php $fpAdminTechnicalMenuTables = ['footer_settings', 'footer_links', 'footer_phones']; ?>

                <?php foreach ($this->menu as $table=>$item):?>
                  <?php if (
                      ($item['menu'] ?? true) === false
                      || in_array($table, $fpAdminTechnicalMenuTables, true)
                  ) continue; ?>
                  <?php $fpAdminMenuLabel = $item['name'] ?? $table; ?>
                  <a
                      href="<?=$this->adminPath?>show/<?=$table?>"
                      class="vg-wrap vg-element vg-full vg-center fp-admin-sidebar__link <?php if($table === ($this->table ?? '')) echo 'active'?>"
                      title="<?=htmlspecialchars((string)$fpAdminMenuLabel, ENT_QUOTES, 'UTF-8')?>"
                      draggable="true"
                      data-fp-admin-menu-table="<?=htmlspecialchars((string)$table, ENT_QUOTES, 'UTF-8')?>"
                  >
                      <span
                          class="fp-admin-sidebar__drag-handle"
                          data-fp-admin-menu-drag-handle
                          aria-hidden="true"
                          title="Перетягнути розділ"
                      >⋮⋮</span>
                      <span class="vg-element vg-half vg-center fp-admin-sidebar__icon">
                          <span>
                              <img src="<?=PATH . ADMIN_TEMPLATE?>img/<?=!empty($item['img']) ? $item['img'] : 'pages.png'?>" alt="">
                          </span>
                      </span>
                      <span class="vg-element vg-half vg-center fp-admin-sidebar__label">
                          <span class="vg-text vg-firm-color5"><?=htmlspecialchars((string)$fpAdminMenuLabel, ENT_QUOTES, 'UTF-8')?></span>
                      </span>
                  </a>
                  <?php endforeach;?>

            <?php endif;?>
        </nav>

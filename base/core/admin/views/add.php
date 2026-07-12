<form id="main-form" class="vg-wrap vg-element vg-ninteen-of-twenty" method="post" action="<?=$this->adminPath . $this->action?>"
      enctype="multipart/form-data">
    <div class="vg-wrap vg-element vg-full">
        <div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow">
            <div class="vg-element vg-half vg-left">
                <div class="vg-element vg-padding-in-px">
                    <input type="submit" class="vg-text vg-firm-col or1 vg-firm-background-color4 vg-input vg-button" value="Зберегти">
                </div>
                <?php if(!$this->noDelete && $this->data):?>
                    <div class="vg\-element vg-padding-in-px">
                        <a href="<?=$this->adminPath . 'delete/' . $this->table . '/' . $this->data[$this->columns['id_row']]?>"
                           class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button vg-center vg_delete">
                            <span>Видалити</span>
                        </a>
                    </div>
                <?php endif;?>
            </div>
        </div>
    </div>

    <?php if($this->data):?>
         <input id="tableId" type="hidden" name="<?=$this->columns['id_row']?>" value="<?=$this->data[$this->columns['id_row']]?>">
    <?php endif;?>
    <input type="hidden" name="table" value="<?=$this->table?>">

    <?php

        $forprintDetailsTabRows = $this->table === 'goods' ? [
            'tab_details_enabled',
            'tab_details_title',
        ] : [];

        $forprintOptionalTabRows = $this->table === 'goods' ? [
            'tab_specs_enabled',
            'tab_specs_title',
            'tab_specs_content',
            'tab_conditions_enabled',
            'tab_conditions_title',
            'tab_conditions_content',
        ] : [];

        $forprintAdminHiddenTabRows = array_merge($forprintDetailsTabRows, $forprintOptionalTabRows);

        foreach($this->blocks as $class => $block){

            if(is_int($class))$class = 'vg-rows';

            echo '<div class="vg-wrap vg-element ' . $class . '">';

            if($class !== 'vg-content') echo '<div class="vg-full vg-firm-background-color4 vg-box-shadow">';

            if($block){
                foreach ($block as $row) {
                    if (!empty($forprintAdminHiddenTabRows) && in_array($row, $forprintAdminHiddenTabRows, true)) {
                        continue;
                    }

                    if (!empty($forprintDetailsTabRows) && $row === 'content') {
                        foreach ($forprintDetailsTabRows as $forprintDetailsRow) {
                            $row = $forprintDetailsRow;

                            foreach ($this->templateArr as $template => $items){
                                if(in_array($row, $items)){
                                    if(!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php'){
                                        throw new \core\base\exceptions\RouteException('Не знайдений шаблон ' .
                                            $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php');
                                    }
                                    break;
                                }
                            }
                        }

                        $row = 'content';
                    }

                    foreach ($this->templateArr as $template => $items){
                        if(in_array($row, $items)){
                            if(!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php'){
                                throw new \core\base\exceptions\RouteException('Не знайдений шаблон ' .
                                    $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php');
                            }
                            break;
                        }
                    }
                }
            }
            if($class !== 'vg-content') echo '</div>';
            echo '</div>';
        }

        if (!empty($forprintOptionalTabRows)) {
            $class = 'vg-optional-tabs';

            echo '<div class="vg-wrap vg-element vg-full forprint-optional-tabs-admin">';
            echo '<div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow">';
            echo '<div class="vg-element vg-full vg-left" style="padding: 18px 18px 8px;">';
            echo '<span class="vg-header">Службові вкладки товару</span>';
            echo '<span class="vg_subheader" style="display:block; margin-top:6px;">Назви, перемикачі та тексти додаткових вкладок товару.</span>';
            echo '</div>';

            foreach ($forprintOptionalTabRows as $row) {
                foreach ($this->templateArr as $template => $items) {
                    if (in_array($row, $items, true)) {
                        if (!@include $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php') {
                            throw new \core\base\exceptions\RouteException('Не знайдений шаблон ' .
                                $_SERVER['DOCUMENT_ROOT'] . $this->formTemplates . $template . '.php');
                        }

                        break;
                    }
                }
            }

            echo '</div>';
            echo '</div>';
        }
    ?>
    <div class="vg-wrap vg-element vg-full">
        <div class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow">
            <div class="vg-element vg-half vg-left">
                <div class="vg-element vg-padding-in-px">
                    <input type="submit"
                           class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button"
                           value="Зберегти">
                </div>
                <div class="vg-element vg-padding-in-px">
                    <a href="/admin/shop/delete/table/shop_products/id_row/id/id/92"
                       class="vg-text vg-firm-color1 vg-firm-background-color4 vg-input vg-button vg-center vg_delete">
                        <span>Видалити</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

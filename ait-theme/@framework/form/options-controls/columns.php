<?php


class AitColumnsOptionControl extends AitOptionControl
{

    protected static $layouts = array(
        '1' => array(
            'column-grid-2' => array(
                'column-span-2' => array(
                    'column-span-2' => '1/1'
                )
            )
        ),
        '2' => array(
            'column-grid-2' => array(
                'column-span-1,column-span-1' => array(
                    'column-span-1' => '1/2'
                )
            ),
            'column-grid-3' => array(
                'column-span-2,column-span-1' => array(
                    'column-span-2' => '2/3',
                    'column-span-1' => '1/3'
                )
            ),
            'column-grid-4' => array(
                'column-span-3,column-span-1' => array(
                    'column-span-3' => '3/4',
                    'column-span-1' => '1/4'
                )
            ),
            'column-grid-5' => array(
                'column-span-4,column-span-1' => array(
                    'column-span-1' => '1/5',
                    'column-span-4' => '4/5'
                ),
                'column-span-3,column-span-2' => array(
                    'column-span-2' => '2/5',
                    'column-span-3' => '3/5'
                )
            ),
        ),
        '3' => array(
            'column-grid-3' => array(
                'column-span-1,column-span-1,column-span-1' => array(
                    'column-span-1' => '1/3'
                )
            ),
            'column-grid-4' => array(
                'column-span-2,column-span-1,column-span-1' => array(
                    'column-span-1' => '1/4',
                    'column-span-2' => '2/4'
                )
            ),
            'column-grid-5' => array(
                'column-span-3,column-span-1,column-span-1' => array(
                    'column-span-1' => '1/5',
                    'column-span-3' => '3/5'
                ),
                'column-span-2,column-span-2,column-span-1' => array(
                    'column-span-1' => '1/5',
                    'column-span-2' => '2/5'
                )
            ),
        ),
        '4' => array(
            'column-grid-4' => array(
                'column-span-1,column-span-1,column-span-1,column-span-1' => array(
                    'column-span-1' => '1/4'
                )
            ),
            'column-grid-5' => array(
                'column-span-2,column-span-1,column-span-1,column-span-1' => array(
                    'column-span-1' => '1/5',
                    'column-span-2' => '2/5'
                )
            ),
        ),
        '5' => array(
            'column-grid-5' => array(
                'column-span-1,column-span-1,column-span-1,column-span-1,column-span-1' => array(
                    'column-span-1' => '1/5'
                )
            )
        ),
        '6' => array(
            'column-grid-6' => array(
                'column-span-1,column-span-1,column-span-1,column-span-1,column-span-1,column-span-1' => array(
                    'column-span-1' => '1/6'
                )
            )
        )
    );

    protected $selectedLayout = array();


    protected function control()
	{
		?>
        <div class="ait-grid">
            <div class="ait-grid-row">
                <div class="ait-top-panel">
                    <div class="btn-groups">
                        <?php foreach (self::$layouts as $columnsCount => $gridsOptions): ?>
                            <?php
                                $hasOnlyOneLayoutOption = false;
                                $columnsCssClassesOption = '';
                                foreach ($gridsOptions as $columnsCssClassesOptions) {
                                    foreach (array_keys($columnsCssClassesOptions) as $columnsCssClassesOption) {
                                        if (!$hasOnlyOneLayoutOption) {
                                            $hasOnlyOneLayoutOption = true;
                                        } else {
                                            $hasOnlyOneLayoutOption = false;
                                            break 2;
                                        }
                                    }
                                }
                            ?>
                            <?php if ($hasOnlyOneLayoutOption): ?>
                                <div class="btn-group">
                                    <?php $columnsNames = array();
                                    $columnsCssClasses = explode(',', $columnsCssClassesOption);
                                    $gridOption = current($gridsOptions);
                                    foreach ($columnsCssClasses as $columnsCssClass) {
                                        array_push($columnsNames, $gridOption[$columnsCssClassesOption][$columnsCssClass]);
                                    } ?>
                                    <span class="btn change-columns" data-ait-grid-css-class="<?php echo key($gridsOptions); ?>" data-ait-columns-css-classes="<?php echo key(current($gridsOptions)) ?>" data-ait-columns-names="<?php echo implode(',', $columnsNames); ?> ">
                                        <span class="btn-icon"><?php _e('1 Col', 'ait-admin') ?></span>
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="btn-group">
                                <span class="btn dropdown-toggle" data-toggle="dropdown">
                                        <span class="btn-icon"><?php echo $columnsCount; ?></span>
                                        <span class="caret"></span>
                                    </span>
                                <ul class="dropdown-menu">
                                <?php
                                    foreach ($gridsOptions as $gridCssClass => $columnsCssClassesOptions):
                                        foreach (array_keys($columnsCssClassesOptions) as $columnsCssClassesOption):
                                            $columnsNames = array();
                                            $columnsCssClasses = explode(',', $columnsCssClassesOption);
                                            foreach ($columnsCssClasses as $columnsCssClass) {
                                                array_push($columnsNames, $columnsCssClassesOptions[$columnsCssClassesOption][$columnsCssClass]);
                                            }
                                            ?>
                                            <li><a class="change-columns" data-ait-grid-css-class="<?php echo $gridCssClass ?>" data-ait-columns-css-classes="<?php echo $columnsCssClassesOption; ?>" data-ait-columns-names="<?php echo implode(',', $columnsNames); ?>" href="#"><?php echo implode(' ', $columnsNames) ?></a></li>
                                            <?php
                                        endforeach;
                                     endforeach;
                                ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                </div>
                <div class="ait-table-content">
                <div class="ait-row-content column-grid <?php echo $this->getValue('grid-css-class'); ?>">
                <?php

                $columnsCssClasses = array_map('trim', explode(',', $this->getValue('columns-css-classes')));
                foreach ($columnsCssClasses as $columnCssClass):
                ?>
                    <div class="ait-column <?php echo $columnCssClass ?>" data-ait-column-css-class="<?php echo $columnCssClass ?>"><div class="ait-column-handle"><h4><?php echo $this->resolveColumnNameForColumnCssClass($columnCssClass); ?></h4></div><div class="ait-column-content"></div></div>
                <?php
                endforeach;
                ?>
                </div>
                </div>
                <input type="hidden" id="<?php echo $this->getIdAttr('grid-css-class'); ?>" name="<?php echo $this->getNameAttr('grid-css-class'); ?>" value="<?php echo esc_attr($this->getValue('grid-css-class')) ?>" />
                <input type="hidden" id="<?php echo $this->getIdAttr('columns-css-classes'); ?>" name="<?php echo $this->getNameAttr('columns-css-classes'); ?>" value="<?php echo esc_attr($this->getValue('columns-css-classes')) ?>" />
            </div>
            <div class="ait-columns-editor hidden">
                <div class="ait-columns-editor-element-header">
                    <div class="ait-columns-editor-element-title"><h4></h4><span title="<?php _e('Edit element description', 'ait-admin'); ?>" class="ait-element-user-description"></span></div>
                    <a class="ait-columns-editor-remove" href="#">x</a>
                </div>
                <div class="ait-columns-editor-element-options"></div>
            </div>
		</div>
		<?php
	}



    private function resolveColumnNameForColumnCssClass($columnCssClass)
    {
        $columnName = '';

        $columnsCssClasses = array_map('trim', explode(',', $this->getValue('columns-css-classes')));
        $columnsCssClassesOptions = self::$layouts[count($columnsCssClasses)][$this->getValue('grid-css-class')];
        foreach (array_keys($columnsCssClassesOptions) as $columnsCssClassesOption) {
            foreach ($columnsCssClasses as $class) {
                if (strpos($columnsCssClassesOption, $class) === false) {
                    continue 2;
                }
            }
            $columnName = $columnsCssClassesOptions[$columnsCssClassesOption][$columnCssClass];
        }

        return $columnName;
    }

}


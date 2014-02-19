<?php
/**
 * @package ImpressPages

 *
 */
namespace Ip\Internal\Content\Widget\Gallery;




class Controller extends \Ip\WidgetController{

    public function getTitle() {
        return __('Gallery', 'ipAdmin', false);
    }



    public function update($widgetId, $postData, $currentData) {

        if (isset($postData['method'])) {
            switch($postData['method']) {
                case 'move':
                    if (!isset($postData['originalPosition'])) {
                        throw new \Ip\Exception("Missing required parameter");
                    }
                    $originalPosition = $postData['originalPosition'];
                    if (!isset($postData['newPosition'])) {
                        throw new \Ip\Exception("Missing required parameter");
                    }
                    $newPosition = $postData['newPosition'];

                    if (!isset($currentData['images'][$originalPosition])) {
                        throw new \Ip\Exception("Moved image doesn't exist");
                    }

                    $movedImage = $currentData['images'][$originalPosition];
                    unset($currentData['images'][$originalPosition]);
                    array_splice($currentData['images'], $newPosition, 0, array($movedImage));
                    return $currentData;
                case 'add':
                    if (!isset($postData['images']) || !is_array($postData['images'])) {
                        throw new \Ip\Exception("Missing required parameter");
                    }


                    foreach($postData['images'] as $image){
                        if (!isset($image['fileName']) || !isset($image['status'])){ //check if all required data present
                            continue;
                        }

                        //just to be sure
                        if (!file_exists(ipFile('file/repository/' . $image['fileName']))) {
                            continue;
                        }

                        //bind new image to the widgetx
                        \Ip\Internal\Repository\Model::bindFile($image['fileName'], 'Content', $widgetId);


                        //find image title
                        if (!empty($image['title'])) {
                            $title = $image['title'];
                        } else {
                            $title = basename($image['fileName']);
                        }

                        $newImage = array(
                            'imageOriginal' => $image['fileName'],
                            'title' => $title,
                        );

                        $currentData['images'][] = $newImage;
                    }

                    return $currentData;
                case 'crop':
                    break;
                case 'update' :

                    $tmpData = $currentData['images'][$postData['imageIndex']];
                    if ($tmpData['imageOriginal'] != $postData['fileName']) {
                        $this->_deleteOneImage($tmpData, $widgetId);
                        //bind new image to the widget
                        \Ip\Internal\Repository\Model::bindFile($postData['fileName'], 'Content', $widgetId);
                        $tmpData['imageOriginal'] = $postData['fileName'];
                    }

                    //check if crop coordinates are set
                    if (isset($postData['cropX1']) && isset($postData['cropY1']) && isset($postData['cropX2']) && isset($postData['cropY2'])) {
                        $tmpData['cropX1'] = $postData['cropX1'];
                        $tmpData['cropY1'] = $postData['cropY1'];
                        $tmpData['cropX2'] = $postData['cropX2'];
                        $tmpData['cropY2'] = $postData['cropY2'];
                    }

                    $currentData['images'][$postData['imageIndex']] = $tmpData;
                    return $currentData;
                    break;

//                case 'setTitle':
//                    //find image title
//                    if ($image['title'] == '') {
//                        $title = basename($image['fileName']);
//                    } else {
//                        $title = $image['title'];
//                    }
//                    break;
                case 'setLink':
                    if (!isset($postData['index'])) {
                        throw new \Ip\Exception("Missing required parameter");
                    }
                    $index = $postData['index'];
                    if (isset($postData['type'])) {
                        $currentData['images'][$index]['type'] = $postData['type'];
                    }
                    if (isset($postData['url'])) {
                        $currentData['images'][$index]['url'] = $postData['url'];
                    }
                    if (isset($postData['blank'])) {
                        $currentData['images'][$index]['blank'] = $postData['blank'];
                    }
                    return $currentData;

                    break;
                case 'delete':
                    if (!isset($postData['position'])) {
                        throw new \Ip\Exception("Missing required parameter");
                    }
                    $deletePosition = (int)$postData['position'];


                    $this->_deleteOneImage($currentData['images'][$deletePosition], $widgetId);

                    unset($currentData['images'][$deletePosition]);
                    $currentData['images'] = array_values($currentData['images']); // 'reindex' array
                    return $currentData;
                default:
                    throw new \Ip\Exception('Unknown command');

            }
        }




        return $currentData;
    }


    public function adminHtmlSnippet()
    {
        $variables = array (
            'linkForm' => $this->linkForm()
        );
        return ipView('snippet/gallery.php', $variables)->render();

    }



    private function _findExistingImage ($imageOriginalFile, $allImages) {

        if (!is_array($allImages)) {
            return false;
        }

        $answer = false;
        foreach ($allImages as $imageKey => $image) {
            if (isset($image['imageOriginal']) && $image['imageOriginal'] == $imageOriginalFile) {
                $answer = $image;
                break;
            }
        }

        return $answer;

    }




    public function generateHtml($revisionId, $widgetId, $instanceId, $data, $skin)
    {

        if (isset($data['images']) && is_array($data['images'])) {
            //loop all current images
            foreach ($data['images'] as &$curImage) {
                if (empty($curImage['imageOriginal'])) {
                    continue;
                }
                $desiredName = isset($curImage['title']) ? $curImage['title'] : '';

                //create big image reflection
                $bigWidth = ipGetOption('Config.lightboxWidth', 800);
                $bigHeight = ipGetOption('Config.lightboxHeight', 600);

                try {
                    $transformBig = new \Ip\Transform\ImageFit($bigWidth, $bigHeight);
                    $curImage['imageBig'] = ipFileUrl(ipReflection($curImage['imageOriginal'], $desiredName, $transformBig));
                } catch (\Ip\Internal\Repository\TransformException $e) {
                    $curImage['imageBig'] = '';
                    //do nothing
                } catch (\Ip\Internal\Repository\Exception $e) {
                    $curImage['imageBig'] = '';
                    //do nothing
                }



                try {
                    if (isset($curImage['cropX1']) && isset($curImage['cropY1']) && isset($curImage['cropX2']) && isset($curImage['cropY2']) ) {
                        $transformSmall = new \Ip\Transform\ImageCrop(
                            $curImage['cropX1'],
                            $curImage['cropY1'],
                            $curImage['cropX2'],
                            $curImage['cropY2'],
                            ipGetOption('Content.widgetGalleryWidth'),
                            ipGetOption('Content.widgetGalleryHeight'),
                            ipGetOption('Content.widgetGalleryQuality')
                        );

                    } else {
                        $transformSmall = new \Ip\Transform\ImageCropCenter(
                            ipGetOption('Content.widgetGalleryWidth'),
                            ipGetOption('Content.widgetGalleryHeight'),
                            ipGetOption('Content.widgetGalleryQuality')
                        );

                    }
                    $curImage['imageSmall'] = ipFileUrl(ipReflection($curImage['imageOriginal'], $curImage['title'], $transformSmall));
                } catch (\Ip\Internal\Repository\TransformException $e) {
                    //do nothing
                } catch (\Ip\Internal\Repository\Exception $e) {
                    //do nothing
                }

                if (empty($curImage['type'])) {
                    $curImage['type'] = 'lightbox';
                }
                if (empty($curImage['url'])) {
                    $curImage['url'] = '';
                }
                if (empty($curImage['blank'])) {
                    $curImage['blank'] = '';
                }
                if (empty($curImage['title'])) {
                    $curImage['title'] = '';
                }


            }
        }
        return parent::generateHtml($revisionId, $widgetId, $instanceId, $data, $skin);
    }


    public function delete($widgetId, $data) {
        if (!isset($data['images']) || !is_array($data['images'])) {
            return;
        }

        foreach($data['images'] as $imageKey => $image) {
            self::_deleteOneImage($image, $widgetId);
        };
    }

    private function _deleteOneImage($image, $widgetId) {
        if (!is_array($image)) {
            return;
        }
        if (isset($image['imageOriginal']) && $image['imageOriginal']) {
            \Ip\Internal\Repository\Model::unbindFile($image['imageOriginal'], 'Content', $widgetId);
        }
    }





    /**
    *
    * Duplicate widget action. This function is executed after the widget is being duplicated.
    * All widget data is duplicated automatically. This method is used only in case a widget
    * needs to do some maintenance tasks on duplication.
    * @param int $oldId old widget id
    * @param int $newId duplicated widget id
    * @param array $data data that has been duplicated from old widget to the new one
    */
    public function duplicate($oldId, $newId, $data) {
        if (!isset($data['images']) || !is_array($data['images'])) {
            return;
        }

        foreach($data['images'] as $imageKey => $image) {
            if (!is_array($image)) {
                return;
            }
            if (isset($image['imageOriginal']) && $image['imageOriginal']) {
                \Ip\Internal\Repository\Model::bindFile($image['imageOriginal'], 'Content', $newId);
            }
        }

    }


    protected function linkForm()
    {
        $form = new \Ip\Form();

        $field = new \Ip\Form\Field\Select(
            array(
                'name' => 'type',
                'label' => __('Mouse click action', 'ipAdmin', false),
            ));

        $values = array(
            array('lightbox', __('Lightbox', 'ipAdmin', false)),
            array('link', __('URL', 'ipAdmin', false)),
            array('nothing', __('Nothing', 'ipAdmin', false)),
        );
        $field->setValues($values);
        $form->addfield($field);


        $field = new \Ip\Form\Field\Text(
            array(
                'name' => 'url',
                'label' => __('Url', 'ipAdmin', false),
            ));
        $form->addField($field);


        $field = new \Ip\Form\Field\Checkbox(
            array(
                'name' => 'blank',
                'label' => __('Open in new window', 'ipAdmin', false),
            ));
        $form->addField($field);

        return $form; // Output a string with generated HTML form
    }

}

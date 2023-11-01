<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PurchaseOrder\Controller\Ui;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Ui\Controller\Index\Render as UiRender;

/**
 * Render UI Controller to prevent incorrect i18n translations during ui update
 */
class Render extends UiRender implements HttpGetActionInterface
{

}

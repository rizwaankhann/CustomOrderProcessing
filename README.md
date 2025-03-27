# SmartWorking_CustomOrderProcessing module

Custom Module for updating order status via Magento API and db logger for the same.
Please check bottom of readme file to see latest changes pushed/done

## Module Overview
    The module introduces the following features:

    => Custom REST API Endpoint: Allows external systems to update the status of an existing Magento order using a POST request.
    => Order Status Change Logging: Captures and logs order status changes (including order ID, old status, new status, and timestamp) into a custom database table.

## Module Details
    Namespace: SmartWorking\CustomOrderProcessing
    Module Name: CustomOrderProcessing
    Database Table: custom_order_processing_logger

## Installation
    Place the Module Files:
    Copy the module folder (SmartWorking\CustomOrderProcessing) into the app/code/ directory of your Magento 2 installation.
    Final path: app/code/SmartWorking\CustomOrderProcessing
    Enable the Module: Run the following commands from the Magento root directory:

    php bin/magento setup:upgrade
    php bin/magento setup:di:compile
    php bin/magento setup:static-content:deploy
    php bin/magento cache:clean

## Features
    => Custom REST API Endpoint
       Endpoint: POST /rest/V1/customUpdateOrderStatus
       Purpose: Updates the status of an order based on the provided order increment ID and new status.
       Json Request Body Example:
    {
        "data": {
            "order_increment_id": "000000005",
            "new_order_status": "processing"
        }
    }

    Response: Returns a success message or an error if the order is not found or the status update fails.

    To pass the bearer token you need admin account which has the sales ACL permission.
    To create admin token use below endpoint which will return your the token

    Endpoint: V1/integration/admin/token
    Post Data: {
                "username": "admin",
                "password": "Admin@123"
            }

    CURL Request: 
        curl -X POST \
        -H "Content-Type: application/json" \
        -d '{
        "username": "admin",
        "password": "Admin@123"
        }' \
        http://yourlocal.domain/rest/V1/integration/admin/token

    Response: it will return you the token for admin copy that token and pass as bearer token when calling above API.

## Order Status Change Logger
    Event: Triggered whenever an order status is updated (via API or otherwise).
    Observer: Listens to the sales_order_save_commit_after event.
    Action: it will save logs the following details into the custom_order_processing_logger table:

    order_id: The ID of the order.
    old_status: The previous status of the order.
    current_status: The updated status of the order.
    created_at & update_at: The date and time of the status change.


## Checking the Logs
    Query the custom_order_processing_logger table in your database to view the logged status changes:

    SELECT * FROM custom_order_processing_logger ORDER BY id DESC;

## Configuration

    Additional Store configuration is created also which is helpful to enable and disable the functionality.

    Path: Store-> Configuration-> Smart Working-> General Configuration For Custom Order Update-> Enable Custom Order Status Update Functionality -> Yes/No 

## Future Enhancement  
    Cover more unit tests for API and observer,
    Create a backend ui component grid to view logs in magento admin. [Done]

## Added Ui-Component Grid in backend in SmartWorking -> Order Status Change Logs with magento standard.

## Enhanced the validation for the webapi for different scenerios. 

## Added UNIT test coverage scenerios, please run below commands to execute this module specific  unit test cases
    Navigate to your project root directory from terminal.
    Go to               cd dev/tests/unit/
    Run Unit Test       ../../../vendor/bin/phpunit -c phpunit.xml.dist ../../../app/code/SmartWorking/CustomOrderProcessing/Test/Unit/Model/Api/OrderStatusUpdateSubmitTest.php

    


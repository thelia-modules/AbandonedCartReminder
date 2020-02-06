{extends file="email-layout.tpl"}

{* Do not provide a "Open in browser" link  *}
{block name="browser"}{/block}
{* No pre-header *}
{block name="pre-header"}{/block}

{* Subject  *}
{block name="email-subject"}{intl l="It seems that you forgot your cart !" d="abandonedcartreminder"}{/block}

{* Title  *}
{block name="email-title"}{intl l="Your order is waiting for you !" d="abandonedcartreminder"}{/block}

{* -- Declare assets directory, relative to template base directory --------- *}
{declare_assets directory='assets'}

{* Set the default translation domain, that will be used by {intl} when the 'd' parameter is not set *}
{default_translation_domain domain='bo.default'}

{* Content  *}
{block name="email-content"}
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            background-color: #e5e3e3;
            font-family:'Josefin Sans', sans-serif;
        }

        table {
            table-layout: unset !important;
        }

        table.main {
            margin: 0 auto;
            width: 100%;
        }

        p {
            margin-top: 10px;
            text-align: center;
            margin-bottom: 10px;
        }

        p.left {
            text-align: left;
        }

        table.wrapper {
            width: 100%;
        }

        td.separator p {
            background-color: #f9f5f4;
            height: 45px;
            margin: 0;
        }

        td.head p {
            background-color: #3f3a38;
            height: 50px;
            margin: 0;
        }

        td.head-bottom p {
            height: 220px;
        }

        td.sidebar hr {
            width: 30%;
        }

        .title {
            font-weight: bold;
            font-size: 140%;
        }

        .wrapper-panier {
            background-color: #fff;
            padding: 10px;
            margin: auto;
        }

        table.panier {
            border-spacing: 0;
            border-collapse : collapse;
            width: 100%;
        }

        table.panier td {
            color: #6d5f5a;
            vertical-align: top;
            padding: 10px;
        }

        table.panier td.head {
            font-weight: 600;
            border-bottom: 1px solid #6d5f5a;
        }

        table.panier td.foot {
            font-weight: 600;
            border-top: 1px solid #6d5f5a;
            border-bottom: 1px solid #a79893;
        }

        table.panier td p {
            margin-top: 0;
        }

        table.panier td small {
            font-size: 75%;
        }

        .product-attributes, .product-attributes li {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .btn-panier {
            color: #fff;
            background-color: #f15a24;
            padding: 10px 20px;
            text-transform: uppercase;
            text-decoration: none;
        }

    </style>

    <table class="main">
        <tr>
            <td>
                <table width="100%" class="wrapper">
                    <tr>
                        {* mail content *}
                        <td class="content">
                            {block name="contenu"}{/block}
                            
                            <div class="wrapper-panier">
                                <table width="100%" class="panier">
                                <tr>
                                    <td class="head">{intl l="Product" d="abandonedcartreminder"}</td>
                                    <td class="head">{intl l="Product Name" d="abandonedcartreminder"}</td>
                                    <td class="head" style="text-align: center;">{intl l="Price" d="abandonedcartreminder"}</td>
                                    <td class="head" style="text-align: center;">{intl l="Amount" d="abandonedcartreminder"}</td>
                                    <td class="head" style="text-align: right;">&nbsp{intl l="Total" d="abandonedcartreminder"}</td>
                                </tr>
                                {loop type="abandonedcart.cartitem" name="pa" cart_id=$cart_id}
                                    <tr>
                                        <td>
                                            {loop type="image" name="pi" source="product" source_id=$PRODUCT_ID width="118" height="85" limit="1" force_return="true"}
                                                <img src="{$IMAGE_URL}">
                                            {/loop}
                                        </td>
                                        
                                        <td>
                                            <p class="left" style="text-transform: uppercase; margin-bottom: 5px;">{$TITLE nofilter}</p>
                                            
                                            <ul class="product-attributes">
                                                {loop type="attribute_combination" name="product_options" product_sale_elements=$PRODUCT_SALE_ELEMENTS_ID order="manual"}
                                                {$title = ($ATTRIBUTE_CHAPO) ? $ATTRIBUTE_CHAPO : $ATTRIBUTE_TITLE}
                                                    <li>{$title}: {$ATTRIBUTE_AVAILABILITY_TITLE}</li>
                                                {/loop}
                                            </ul>
                                        </td>
                                        
                                        <td nowrap="nowrap" style="white-space: nowrap; text-align: right;">
                                            {if $IS_PROMO == 1}
                                                {$unit_price = $PROMO_TAXED_PRICE}
                                                {$unit_price_ht = $PROMO_PRICE}
        
        
                                                <span class="normal-price">{format_money number=$PROMO_TAXED_PRICE}</span>
                                                <br>
                                                <small>{intl l="%price HT" price={format_money number=$PROMO_PRICE}}</small>
                                            {else}
                                                {$unit_price = $TAXED_PRICE}
                                                {$unit_price_ht = $PRICE}
        
                                                {format_money number=$TAXED_PRICE}
                                                <br>
                                                <small>{intl l="%price HT" price={format_money number=$PRICE}}</small>
                                            {/if}
    
                                            {$total_product_price = $total_product_price + $QUANTITY * $unit_price}
                                            {$total_product_price_ht = $total_product_price_ht + $QUANTITY * $unit_price_ht}
                                        </td>
                                        
                                        <td style="text-align: center;">{$QUANTITY}</td>
                                        
                                        <td  nowrap="nowrap" style="white-space: nowrap;text-align: right;">
                                            {format_money number={$QUANTITY * $unit_price}}
                                            <br>
                                            <small>{intl l="%price HT" price={format_money number={$QUANTITY * $unit_price_ht}}}</small>
                                        </td>
                                    </tr>
                                {/loop}
                                <tr>
                                    <td colspan="4" class="foot" style="text-align: right">
                                        TOTAL PRODUITS TTC
                                    </td>
                                    <td nowrap="nowrap" class="foot" style="white-space: nowrap; text-align: right;">
                                        {format_money number=$total_product_price}
                                        <br>
                                        <small>{intl l="%price HT" price={format_money number=$total_product_price_ht}}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="999">
                                        <br>&nbsp;<br>
                                        <p><a class="btn-panier" href="{url path="/back-to-cart/%token" token=$login_token}">{intl l='GO BACK TO MY CART' d="abandonedcartreminder"}</a></p>
                                    </td>
                                </tr>
                            </table>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <br />

    {block name="footer"}
    {/block}

{/block}

# call_picture_from_amazon

Get image URL etc by json from Amazon Product Advertising API.

I used in [anichecker](http://kuje.kousakusyo.info/tools/anichecker/ "anichecker").

## Description

This API throw to Amazon Product Advertising API search keywords and SearchIndex, and get the image, the URL, etc by json.

## Notes

1. This API use '[aws_signed_request.php](http://www.ulrichmierendorff.com/software/aws_hmac_signer.html)', set the same folder.
1. Create temp folder, give write attribute, and write to 'call_picture_from_amazon__config.php'.
1. Get these keys or tag from amazon, and write to 'call_picture_from_amazon__config.php'.
	1. AssociateTag
	1. AWSPublicKey
	1. AWSPrivateKey

## Demo

Use call_picture_from_amazon.html.

Or <http://kuje.kousakusyo.info/tools/iphone/anichecker/>

## Usage

### (get)

'Keyword' keyword.

'SearchIndex' category[ex All(Default), Books, Default].

### (return)

'Items' item(s).

'Items.Item[num]' item (By  Amazon Product Advertising API).

## Contribution

1. Fork it ( http://github.com/hiroshikuze/call_picture_from_amazon/fork )
2. Create your feature branch (git checkout -b my-new-feature)
3. Commit your changes (git commit -am 'Add some feature')
4. Push to the branch (git push origin my-new-feature)
5. Create new Pull Request

## LICENCE

MIT License.

## Author

[hiroshikuze](https://github.com/hiroshikuze)

## Donation

[Author's wish list by Amazon(Japanese)](https://www.amazon.jp/hz/wishlist/ls/5BAWD0LZ89V9?ref_=wl_share)

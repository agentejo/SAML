<?=('<?xml version="1.0"?>')?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     validUntil="{{ date('Y-m-d', strtotime('+ 1 month')) }}T01:04:44Z"
                     cacheDuration="PT604800S"
                     entityID="{{ $meta['entityID'] }}">
    <md:SPSSODescriptor AuthnRequestsSigned="false" WantAssertionsSigned="false" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                                Location="{{ $meta['singleLogoutService'] }}" />
        <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</md:NameIDFormat>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
                                     Location="{{ $meta['assertionConsumerService'] }}"
                                     index="1" />

    </md:SPSSODescriptor>
</md:EntityDescriptor>

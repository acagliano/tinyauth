<div class="exp-item">
<div class="title">What is it?</div>
<p>TInyAuth is an open authentication service for TI-84+ CE graphing calculators that provides an API for credential-less single-sign-on compatible with any network-capable services developed for the calculator.</p>
</div>

<div class="exp-item">
<div class="title">Simple to Use</div>
<p>If you are a developer, simply include the C or Asm static library for loading keyfiles in your calculator project and follow the Server API directions to enable it in your service. If you are an end user of a service that supports it, simply create or log in to an account on this site, generate a token, and export it to a keyfile.</p>
</div>
<div class="exp-item">
<div class="title">Secure Construction</div>
<p>The keyfile consists of a file type identifier, an account identifier, and a digitially-signed authentication token that authenticates the user and also validates that the authenticating server is also the issuing server. A user can instantly invalidate all previously downloaded keyfiles by refreshing their token.</p>
</div>

<div class="exp-item red">
<div class="title">Disclaimer</div>
<p class="note">TInyAuth is not Oauth. It is intended to operate in a similar manner but also be optimized for use on a calculator. This means simpler, smaller tokens that are incompatible with Oauth directly.</p>
</div>

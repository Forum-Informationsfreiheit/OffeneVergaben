<footer>
    <div class="footer-one container-fluid">
        <div class="d-flex">
            <div class="mx-auto logo">
                @svg('img/icons/logo_offenevergaben_footer.svg','Offene Vergaben')
            </div>
        </div>
        <div class="d-flex flex-column">
            <div class="mx-auto justify-content-center">
                <p class="text">Ermöglicht wird dieses Projekt des <a target="_blank" href="https://www.informationsfreiheit.at/">Forum Informationsfreiheit</a> durch eine Unterstützung der <a target="_blank" href="https://www.netidee.at/">Netidee</a> der Internet Privatstiftung Austria (IPA).<br />Die Server-Infrastruktur wird durch ein Sponsoring von <a target="_blank" href="https://easyname.com">Easyname</a> bereitgestellt</p>
            </div>
        </div>
        <div class="d-flex">
            <ul class="mx-auto justify-content-center">
                <li>
                    <a href="{{ url('/überuns') }}">Über uns</a>
                </li>
                <li>
                    <a href="{{ url('/impressum') }}">Kontakt & Impressum</a>
                </li>
                <li>
                    <a href="{{ url('/datenschutz') }}">Datenschutzerklärung</a>
                </li>
                <li>
                    <a href="{{ url('/frequently-asked-questions') }}">FAQ</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="footer-two container-fluid">
        <div class="d-flex">
            <ul class="mx-auto justify-content-center">
                <li>
                    <a target="_blank" href="https://www.informationsfreiheit.at/"><img src="{{ url('/img/logo/foi_logo_w2.gif') }}"></a>
                </li>
                <li>
                    <a target="_blank" href="https://www.netidee.at/">
                        <!-- <img src="{{ url('/img/logo/netidee-logo-color.jpg') }}"> -->
                        @svg('/img/logo/netidee-logo-white.svg','netidee')
                    </a>
                </li>
                <li>
                    <a target="_blank" href="https://www.easyname.com/">
                        <!-- <img src="{{ url('/img/logo/netidee-logo-color.jpg') }}"> -->
                        @svg('/img/logo/easyname_logo_flat_white.svg','easyname')
                    </a>
                </li>
            </ul>
        </div>
    </div>
</footer>

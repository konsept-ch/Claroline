import React, { useCallback, useState } from 'react'

export const CookiesBanner = () => {
  const [isCookieBannerClosed, setIsCookieBannerClosed] = useState(localStorage.getItem('isCookieBannerClosed'));

  const onCloseButtonClick = useCallback(() => {
    localStorage.setItem('isCookieBannerClosed', true);
    setIsCookieBannerClosed(true)
  }, [])

  return !isCookieBannerClosed && (
    <div className="cookies-banner">
      <p>
        Ce site utilise des cookies afin de vous offrir une expérience optimale de navigation. En continuant de visiter
        ce site, vous acceptez l'utilisation de ces cookies.
      </p>
      <div>
        <a href="#/home/cg">
          Pour en savoir plus sur comment les désactiver, ainsi que sur notre politique en matière de protection des
          données
        </a>
      </div>
      <span className="fa fa-times close-cookie-banner-button" onClick={onCloseButtonClick} />
    </div>
  )
}

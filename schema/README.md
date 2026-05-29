# `schema/` — entitetsscheman (spegel, ARKITEKTURPLAN FAS 0–1)

Dessa JSON-filer är **spegelkopior** av de kanoniska scheman som bor i
`wexoeplugins/wexoe-core/schema/`. De definierar en entitets fältlista *en gång*
och konsumeras här av `lib/schema/to-state.ts` (record → state), samtidigt som
wexoe-core (PHP) läser samma fil för sin read-normalisering.

> **Håll i synk:** ändrar du ett schema — uppdatera båda kopiorna i samma
> change-set. Detta är den enda sanktionerade dupliceringen (samma princip som
> `ARKITEKTURPLAN.md`); den ersätts av ett delat npm-paket / git-submodule när
> fler entiteter migrerats. Formatspecen finns i wexoe-core:s `schema/README.md`.

Status: `cms_customer_type_pages` är piloten (FAS 1). Övriga entiteter migreras
en i taget.

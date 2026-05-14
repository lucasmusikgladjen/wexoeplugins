/**
 * TS-interfaces för normaliserade SSOT-records.
 *
 * Format speglar Wexoe Core domain-keys (lower_snake_case) snarare än
 * Airtable-fältnamn (Title Case With Spaces).
 */

export interface CoreRecordCommon {
  _recordId: string;
}

export interface CoreCompany extends CoreRecordCommon {
  slug: string;
  is_default: boolean;
  country_ids: string[];
  tagline: string;
  org_number: string;
  vat_number: string;
  email: string;
  email_order: string;
  phone: string;
  phone_emergency: string;
  address_line_1: string;
  address_postal_code: string;
  address_city: string;
  linkedin_url: string;
  facebook_url: string;
  instagram_url: string;
  youtube_url: string;
  hours_mon_thur: string;
  hours_friday: string;
  internal_notes: string;
}

export interface CoreGraphicProfile extends CoreRecordCommon {
  slug: string;
  is_default: boolean;
  logo_primary: string;
  logo_dark_background: string;
  favicon: string;
  color_primary: string;
  color_secondary: string;
  color_accent: string;
  color_background_light: string;
  color_background_dark: string;
  color_text_primary: string;
  color_text_secondary: string;
  font_heading: string;
  font_body: string;
  font_css_url: string;
  division_ids: string[];
}

export interface CoreCountry extends CoreRecordCommon {
  name: string;
  code: string;
  domain: string;
  url_prefix: string;
  currency: string;
  locale: string;
  default_language: string;
  order: number;
  active: boolean;
}

export interface CoreDivision extends CoreRecordCommon {
  name: string;
  slug: string;
  description: string;
  order: number;
  active: boolean;
  country_ids: string[];
}

export interface CoreCustomerType extends CoreRecordCommon {
  name: string;
  slug: string;
  description: string;
  icon: string;
  order: number;
  active: boolean;
}

export interface CoreCoworker extends CoreRecordCommon {
  full_name: string;
  title: string;
  email: string;
  phone: string;
  image: string;
  linkedin_url: string;
  bio: string;
  order: number;
  active: boolean;
  division_ids: string[];
  country_ids: string[];
}

export interface CorePartner extends CoreRecordCommon {
  name: string;
  logo: string;
  logo_transparent: string;
  url: string;
  description: string;
  order: number;
  active: boolean;
  division_ids: string[];
  country_ids: string[];
}

export interface CoreTestimonial extends CoreRecordCommon {
  internal_name: string;
  quote: string;
  author_name: string;
  author_title: string;
  author_image: string;
  order: number;
  active: boolean;
  featured: boolean;
  customer_type_ids: string[];
  division_ids: string[];
  country_ids: string[];
}

export type CoreEntityRecord =
  | CoreCompany
  | CoreGraphicProfile
  | CoreCountry
  | CoreDivision
  | CoreCustomerType
  | CoreCoworker
  | CorePartner
  | CoreTestimonial;

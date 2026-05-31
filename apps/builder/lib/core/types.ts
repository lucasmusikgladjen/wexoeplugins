/**
 * TS-interfaces för normaliserade SSOT-records.
 *
 * Post-migration: snake_case överallt — Airtable display-namn matchar
 * dessa property-namn 1:1 (passthrough).
 */

export interface CoreRecordCommon {
  _recordId: string;
  internal_notes?: string;
}

export interface CoreCompany extends CoreRecordCommon {
  slug: string;
  is_default: boolean;
  country_ids: string[];
  company_name: string;
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
}

export interface CoreGraphicProfile extends CoreRecordCommon {
  slug: string;
  is_default: boolean;
  logo_primary_url: string;
  logo_dark_url: string;
  favicon_url: string;
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
  is_active: boolean;
}

export interface CoreDivision extends CoreRecordCommon {
  name: string;
  slug: string;
  description: string;
  order: number;
  is_active: boolean;
  country_ids: string[];
}

export interface CoreCustomerType extends CoreRecordCommon {
  name: string;
  slug: string;
  description: string;
  icon: string;
  order: number;
  is_active: boolean;
}

export interface CoreCoworker extends CoreRecordCommon {
  full_name: string;
  title: string;
  email: string;
  phone: string;
  image_url: string;
  linkedin_url: string;
  bio: string;
  order: number;
  is_active: boolean;
  division_ids: string[];
  country_ids: string[];
}

export interface CorePartner extends CoreRecordCommon {
  name: string;
  logo_url: string;
  logo_transparent_url: string;
  url: string;
  description: string;
  order: number;
  is_active: boolean;
  division_ids: string[];
  country_ids: string[];
}

export interface CoreTestimonial extends CoreRecordCommon {
  internal_name: string;
  quote: string;
  author_name: string;
  author_title: string;
  author_image_url: string;
  order: number;
  is_active: boolean;
  is_featured: boolean;
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

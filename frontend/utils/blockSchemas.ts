/**
 * Field definitions for blocks edited through Elementor/SchemaSettings.vue
 * instead of a hand-written form in SettingsPanel.vue.
 */
export interface BlockField {
  key: string
  label: string
  type: 'text' | 'textarea' | 'image' | 'select' | 'icon' | 'group' | 'list'
  help?: string
  options?: { value: string; label: string }[]
  /** group: the object's fields; list: each item's fields */
  fields?: BlockField[]
  /** list: label for one item, e.g. "Question" */
  itemLabel?: string
  /** list of plain strings rather than objects */
  plain?: boolean
}

const intro: BlockField[] = [
  { key: 'eyebrow', label: 'Label', type: 'text', help: 'Small pill above the heading, e.g. "ABOUT US"' },
  { key: 'heading', label: 'Heading', type: 'text' },
  { key: 'text', label: 'Intro text', type: 'textarea' },
]

const button = (key: string, label: string): BlockField => ({
  key,
  label,
  type: 'group',
  fields: [
    { key: 'text', label: 'Text', type: 'text' },
    { key: 'url', label: 'Link', type: 'text', help: '/contact or https://...' },
  ],
})

const imageSide = (key: string, label = 'Image position'): BlockField => ({
  key,
  label,
  type: 'select',
  options: [
    { value: 'left', label: 'Left' },
    { value: 'right', label: 'Right' },
  ],
})

/** Complete forms for the section blocks */
export const BLOCK_SCHEMAS: Record<string, BlockField[]> = {
  membership_form: [
    { key: 'heading', label: 'Heading (optional)', type: 'text', help: 'The fields themselves are set in the admin under Membership → Registration form' },
    { key: 'text', label: 'Intro text (optional)', type: 'textarea' },
  ],
  about_split: [
    ...intro,
    { key: 'images', label: 'Photos (two)', type: 'list', plain: true, itemLabel: 'Photo', fields: [{ key: 'value', label: 'Photo', type: 'image' }] },
    imageSide('imagePosition', 'Photos position'),
    { key: 'checklist', label: 'Checklist', type: 'list', plain: true, itemLabel: 'Point', fields: [{ key: 'value', label: 'Text', type: 'text' }] },
    button('primaryButton', 'Main button'),
    button('secondaryButton', 'Second button'),
  ],
  logo_strip: [
    { key: 'eyebrow', label: 'Label', type: 'text' },
    { key: 'heading', label: 'Heading', type: 'text' },
    {
      key: 'logos', label: 'Logos', type: 'list', itemLabel: 'Logo',
      fields: [
        { key: 'image', label: 'Logo', type: 'image' },
        { key: 'alt', label: 'Name', type: 'text' },
        { key: 'url', label: 'Link (optional)', type: 'text' },
      ],
    },
  ],
  feature_split: [
    ...intro,
    { key: 'image', label: 'Photo', type: 'image' },
    imageSide('imagePosition', 'Photo position'),
    {
      key: 'background', label: 'Background', type: 'select',
      options: [{ value: 'tinted', label: 'Tinted' }, { value: 'plain', label: 'Plain' }],
    },
    {
      key: 'features', label: 'Features', type: 'list', itemLabel: 'Feature',
      fields: [
        { key: 'icon', label: 'Icon', type: 'icon' },
        { key: 'title', label: 'Title', type: 'text' },
        { key: 'text', label: 'Text', type: 'textarea' },
      ],
    },
  ],
  testimonials: [
    ...intro,
    {
      key: 'items', label: 'Testimonials', type: 'list', itemLabel: 'Testimonial',
      fields: [
        { key: 'photo', label: 'Photo', type: 'image' },
        { key: 'name', label: 'Name', type: 'text' },
        { key: 'role', label: 'Role', type: 'text' },
        { key: 'quote', label: 'Quote', type: 'textarea' },
      ],
    },
  ],
  cta_band: [
    { key: 'heading', label: 'Heading', type: 'text' },
    { key: 'text', label: 'Text', type: 'textarea' },
    { key: 'buttonText', label: 'Button text', type: 'text' },
    { key: 'buttonUrl', label: 'Button link', type: 'text' },
    { key: 'image', label: 'Photo', type: 'image' },
  ],
  faq: [
    ...intro,
    { key: 'image', label: 'Photo (optional)', type: 'image' },
    {
      key: 'items', label: 'Questions', type: 'list', itemLabel: 'Question',
      fields: [
        { key: 'question', label: 'Question', type: 'text' },
        { key: 'answer', label: 'Answer', type: 'textarea' },
      ],
    },
  ],
}

/**
 * Extra fields for existing blocks, used by theme designs (e.g. Edubright's
 * hero stat card and section labels). Shown below the block's own form.
 */
export const BLOCK_EXTRAS: Record<string, BlockField[]> = {
  hero: [
    {
      key: 'stat', label: 'Floating stat card', type: 'group',
      help: 'Shown by themes that support it (e.g. Edubright)',
      fields: [
        { key: 'value', label: 'Number', type: 'text' },
        { key: 'label', label: 'Label', type: 'text' },
      ],
    },
    {
      key: 'stats', label: 'Stats bar', type: 'list', itemLabel: 'Stat',
      help: 'Shown under the hero by themes that support it',
      fields: [
        { key: 'value', label: 'Number', type: 'text' },
        { key: 'label', label: 'Label', type: 'text' },
      ],
    },
  ],
  dynamic_services: [
    { key: 'eyebrow', label: 'Label', type: 'text', help: 'Small pill above the heading (theme designs)' },
    { key: 'readMoreText', label: 'Button text', type: 'text', help: 'e.g. "View Details"' },
  ],
  dynamic_news: [
    { key: 'eyebrow', label: 'Label', type: 'text', help: 'Small pill above the heading (theme designs)' },
    { key: 'readMoreText', label: 'Button text', type: 'text', help: 'e.g. "See more"' },
  ],
}
